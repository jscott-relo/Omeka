<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 **/

/**
 * 
 *
 * @package Omeka
 * @copyright Center for History and New Media, 2010
 **/
class Omeka_Controller_UsersControllerTest extends Omeka_Test_AppTestCase
{
    
    public function setUp()
    {
        parent::setUp();
                
        // Set the ACL to allow access to users.
        $this->acl->allow(null, 'Users');
        $this->mailHelper = Omeka_Test_Helper_Mail::factory();        
        $this->email = Zend_Registry::get('test_config')->email->to;        
        $this->mailHelper->reset();
    }
    
    public function testAddingNewUserSendsActivationEmail()
    {
        $post = array('username'    => 'foobar',
                      'first_name'  => 'foobar',
                      'last_name'   => 'foobar',
                      'institution' => 'foobar',
                      'email'       => $this->email,
                      'role'        => 'admin');
        $this->getRequest()->setPost($post);
        $this->getRequest()->setMethod('post');
        $this->dispatch('users/add', true);
        $this->assertThat($this->mailHelper->getMailText(), $this->stringContains("Activate your account"));
    }
    
    public function testShowForgotPasswordPage()
    {
        $this->dispatch('users/forgot-password');
        $this->assertNotRedirect();
        $this->assertQuery('form #email');
    }
    
    public function testForgotPasswordForInvalidEmail()
    {
        $invalidEmail = 'bananabanana@example.com';
        $this->request->setPost(array(
            'email' => $invalidEmail
        ));
        $this->request->setMethod('post');
        $this->dispatch('users/forgot-password');
        $this->assertNotRedirect();
        $this->assertQueryContentContains("div.error", "email address", 
            "The form should have responded with an error message indicating that the email address was not found.");
    }
    
    public function testSendingEmailForForgottenPassword()
    {
        $this->request->setPost(array(
            'email' => Omeka_Test_Resource_Db::SUPER_EMAIL
        ));
        $this->request->setMethod('post');
        $this->dispatch('users/forgot-password');
        $mail = $this->mailHelper->getMailText();
        $this->assertThat($mail, $this->stringContains("Subject: [Automated Test Installation] Reset Your Password"));
        $this->assertQueryContentContains("div.success", "Please check your email");
        
        $activationCode = $this->db->fetchOne("SELECT url FROM omeka_users_activations LIMIT 1");
        $this->assertThat($mail, $this->stringContains($activationCode), 
            "Email should contain the activation code send to the user.");
    }
}