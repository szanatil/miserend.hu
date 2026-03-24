<?php

use PHPUnit\Framework\TestCase;
use Api\Login;

class LoginTest extends TestCase {

    // Configuration tests

    public function testLoginRequiresApiVersion4OrHigher() {
        $login = new Login([]);
        
        $this->assertEquals(['>=', 4], $login->requiredVersion);
    }

    public function testLoginHasCorrectTitle() {
        $login = new Login([]);
        
        $this->assertEquals('Felhasználó azonosítás', $login->title);
    }

    // Field definition tests

    public function testLoginDefinesUsernameField() {
        $login = new Login([]);
        
        $this->assertArrayHasKey('username', $login->fields);
    }

    public function testLoginDefinesPasswordField() {
        $login = new Login([]);
        
        $this->assertArrayHasKey('password', $login->fields);
    }

    public function testUsernameFieldIsRequired() {
        $login = new Login([]);
        
        $this->assertTrue($login->fields['username']['required']);
    }

    public function testPasswordFieldIsRequired() {
        $login = new Login([]);
        
        $this->assertTrue($login->fields['password']['required']);
    }

    public function testUsernameFieldValidationIsString() {
        $login = new Login([]);
        
        $this->assertEquals('string', $login->fields['username']['validation']);
    }

    public function testPasswordFieldValidationIsString() {
        $login = new Login([]);
        
        $this->assertEquals('string', $login->fields['password']['validation']);
    }

    public function testUsernameFieldHasDescription() {
        $login = new Login([]);
        
        $this->assertArrayHasKey('description', $login->fields['username']);
        $this->assertNotEmpty($login->fields['username']['description']);
    }

    public function testPasswordFieldHasDescription() {
        $login = new Login([]);
        
        $this->assertArrayHasKey('description', $login->fields['password']);
        $this->assertNotEmpty($login->fields['password']['description']);
    }

    // Documentation tests

    public function testDocsReturnsArray() {
        $login = new Login([]);
        
        $docs = $login->docs();
        
        $this->assertIsArray($docs);
    }

    public function testDocsContainsDescription() {
        $login = new Login([]);
        
        $docs = $login->docs();
        
        $this->assertArrayHasKey('description', $docs);
        $this->assertNotEmpty($docs['description']);
    }

    public function testDocsContainsResponse() {
        $login = new Login([]);
        
        $docs = $login->docs();
        
        $this->assertArrayHasKey('response', $docs);
        $this->assertNotEmpty($docs['response']);
    }

    public function testDocsDescriptionMentionsToken() {
        $login = new Login([]);
        
        $docs = $login->docs();
        
        $this->assertStringContainsString('token', strtolower($docs['description']));
    }

    public function testDocsResponseMentionsError() {
        $login = new Login([]);
        
        $docs = $login->docs();
        
        $this->assertStringContainsString('error', strtolower($docs['response']));
    }

    public function testDocsResponseMentionsToken() {
        $login = new Login([]);
        
        $docs = $login->docs();
        
        $this->assertStringContainsString('token', strtolower($docs['response']));
    }
}
