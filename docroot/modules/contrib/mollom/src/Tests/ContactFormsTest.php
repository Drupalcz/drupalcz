<?php

namespace Drupal\mollom\Tests;

/**
 * Tests protection of Contact module forms.
 * @group mollom
 */
class ContactFormsTest extends MollomTestBase {
  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test', 'contact', 'field_ui', 'text', 'contact_test'];

  public $disableDefaultSetup = TRUE;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  function setUp() {
    parent::setUp();
    $this->setKeys();
    $this->assertValidKeys();

    $this->adminUser = $this->drupalCreateUser(array(
      'access administration pages',
      'administer mollom',
      'access site-wide contact form',
      'access user profiles',
      'access user contact forms',
      'administer contact forms',
      'administer users',
      'administer account settings',
      'administer contact_message fields',
    ));

    $this->webUser = $this->drupalCreateUser(['access site-wide contact form', 'access user profiles', 'access user contact forms']);
  }

  /**
   * Make sure that the user contact form is protected correctly.
   */
  function testProtectContactUserForm() {
    // Enable Mollom for the contact form.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('contact_message_personal_form');
    $this->drupalLogout();

    $this->drupalLogin($this->webUser);
    $url = 'user/' . $this->adminUser->id() . '/contact';
    $button = t('Send message');
    $success = t('Your message has been sent.');

    // Submit a 'spam' message.  This should be blocked.
    $this->assertSpamSubmit($url, ['subject[0][value]', 'message[0][value]'], [], $button);
    $this->assertNoText($success);

    // Submit a 'ham' message.  This should be accepted.
    $this->assertHamSubmit($url, ['subject[0][value]', 'message[0][value]'], [], $button);
    $this->assertText($success);

    // Submit an 'unsure' message.  This should be accepted only after the
    // CAPTCHA has been solved.
    $this->assertUnsureSubmit($url, ['subject[0][value]', 'message[0][value]'], [], $button, $success);
    $this->assertText($success);
  }

  /**
   * Make sure that the site-wide contact form is protected correctly.
   */
  function testProtectContactSiteForm() {
    // Enable Mollom for the contact form.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/contact');
    // Default form exists.
    $this->assertLinkByHref('admin/structure/contact/manage/feedback/delete');

    $this->setProtectionUI('contact_message_feedback_form');
    $this->drupalGet('contact');
    $this->drupalLogout();

    $this->drupalLogin($this->webUser);
    $this->drupalGet('contact');
    $url = 'contact';
    $button = t('Send message');
    $success = t('Your message has been sent.');

    // Submit a 'spam' message.  This should be blocked.
    $this->assertSpamSubmit($url, ['subject[0][value]', 'message[0][value]'], [], $button);
    $this->assertNoText($success);

    // Submit a 'ham' message.  This should be accepted.
    $this->assertHamSubmit($url, ['subject[0][value]', 'message[0][value]'], [], $button);
    $this->assertText($success);

    // Submit an 'unsure' message.  This should be accepted only after the
    // CAPTCHA has been solved.
    $this->assertUnsureSubmit($url, ['subject[0][value]', 'message[0][value]'], [], $button, $success);
    $this->assertText($success);

    // Report the mail to Mollom
    /*
     * @TODO: Find a way to add back in the link to report to mollom.
    $this->drupalGet($found['url']);
    $edit = array(
      'mollom[feedback]' => 'spam',
    );
    $this->drupalPostForm(NULL, $edit, t('Delete'));
    $this->assertText(t('The content was successfully reported as inappropriate.'));
    */
  }
}
