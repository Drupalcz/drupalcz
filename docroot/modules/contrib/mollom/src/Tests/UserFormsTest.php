<?php

namespace Drupal\mollom\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\mollom\Entity\FormInterface;

/**
 * Tests protection of User module forms.
 * @group mollom
 */
class UserFormsTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * Make sure that the request password form is protected correctly.
   */
  function testUserPasswordCaptcha() {
    $this->drupalLogin($this->adminUser);
    // Verify that the protection mode defaults to CAPTCHA.
    $this->drupalGet('admin/config/content/mollom/add-form', ['query' => ['form_id' => 'user_pass']]);
    $this->assertFieldByName('mode', FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->setProtectionUI('user_pass', FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->drupalLogout();

    // Create a new user.
    $this->webUser = $this->drupalCreateUser(array());

    $this->drupalGet('user/password');

    // Try to reset the user's password by specifying an invalid CAPTCHA.
    $edit = ['name' => $this->webUser->getAccountName()];
    $this->postIncorrectCaptcha('user/password', $edit, t('Submit'));
    $this->postCorrectCaptcha(NULL, [], t('Submit'));

    // Try to reset the user's password by specifying a valid CAPTCHA.
    $this->postCorrectCaptcha('user/password', $edit, t('Submit'));
    $this->assertText(t('Further instructions have been sent to your email address.'));
  }

  /**
   * Make sure that the user registration form is protected correctly.
   */
  function testUserRegisterCaptcha() {
    $user_storage = \Drupal::entityManager()->getStorage('user');

    $this->drupalLogin($this->adminUser);
    // Verify that the protection mode defaults to CAPTCHA.
    $this->drupalGet('admin/config/content/mollom/add-form', ['query' => ['form_id' => 'user_register_form']]);
    $this->assertFieldByName('mode', FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->setProtectionUI('user_register_form', FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->drupalLogout();

    // Retrieve initial count of registered users.
    $users = $user_storage->loadMultiple();
    $count_initial = count($users);

    // Validate that the user registration form has a CAPTCHA text field.
    $this->drupalGet('user/register');
    $this->assertCaptchaField();

    // Try to register with an invalid CAPTCHA. Make sure the user did not
    // successfully register.
    $name = $this->randomMachineName();
    $edit = [
      'name' => $name,
      'mail' => $name . '@example.com',
    ];
    $this->postIncorrectCaptcha('user/register', $edit, t('Create new account'));
    $this->assertFalse(user_load_by_name($name), t('The user who attempted to register cannot be found in the database when the CAPTCHA is invalid.'));

    // Verify that user count is still the same.
    $users = $user_storage->loadMultiple();
    $this->assertEqual($count_initial, count($users), t('No new user record has been created.'));

    // Try to register with a valid CAPTCHA. Make sure the user was able
    // to successfully register.
    $this->postCorrectCaptcha('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'));
    /* @var $account \Drupal\user\Entity\User */
    $account = user_load_by_name($edit['name']);
    $this->assertTrue($account, 'New user found after solving CAPTCHA.');
    $data = $this->assertMollomData('user', $account->id());
    $this->assertSame('$data->moderate', $data->moderate, 0);

    // Verify that the user account is deleted after reporting it as spam.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $edit = array(
      'user_cancel_method' => 'user_cancel_delete',
      'mollom[feedback]' => 'spam',
    );
    $this->drupalPostForm(NULL, $edit, t('Cancel account'));
    $user_storage->resetCache();
    $account = $user_storage->load($account->id());
    $this->assertFalse($account, 'Reported user account not found.');
  }

  /**
   * Tests text analysis protection with CAPTCHA for user registration form.
   */
  function testUserRegisterAnalysisCaptcha() {
    $user_storage = \Drupal::entityManager()->getStorage('user');

    // Allow registration by site visitors without administrator approval.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('user_register_form', FormInterface::MOLLOM_MODE_ANALYSIS);
    $this->drupalLogout();

    // Retrieve initial count of registered users.
    $count_initial = count($user_storage->loadMultiple());

    // Verify that a spam user registration is blocked.
    $this->drupalGet('user/register');
    $this->assertNoCaptchaField();
    $edit = [
      'name' => 'spam',
      'mail' => 'spam@example.com',
    ];
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    $this->assertNoCaptchaField();
    $this->assertText(self::SPAM_MESSAGE);
    $count_new = count($user_storage->loadMultiple());
    $this->assertEqual($count_initial, $count_new, 'Existing user count found.');
    $this->assertFalse(user_load_by_name($edit['name']), 'New user not found.');

    // Verify that a unsure registration triggers a CAPTCHA.
    $this->drupalGet('user/register');
    $this->assertNoCaptchaField();
    $edit = [
      'name' => 'unsure',
      'mail' => 'unsure@example.com',
    ];
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    $this->assertCaptchaField();

    // Verify that user count is still the same.
    $count_new = count($user_storage->loadMultiple());
    $this->assertEqual($count_initial, $count_new, 'Existing user count found.');

    // Verify that solving the CAPTCHA registers the user.
    $this->postCorrectCaptcha(NULL, array(), t('Create new account'));
    /* @var $account \Drupal\user\Entity\User */
    $account = user_load_by_name($edit['name']);
    $this->assertTrue($account !== FALSE, 'New user found after solving CAPTCHA.');
    $this->assertTrue($account->isActive(), 'New user account is active.');
    $data = $this->assertMollomData('user', $account->id());
    $this->assertSame('$data->moderate', $data->moderate, 0);
  }

  /**
   * Tests text analysis protection without CAPTCHA for user registration form.
   */
  function testUserRegisterAnalysisModerate() {
    $user_storage = \Drupal::entityManager()->getStorage('user');

    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('user_register_form', FormInterface::MOLLOM_MODE_ANALYSIS, [], [
      'unsure' => 'moderate',
    ]);
    $this->drupalLogout();

    // Retrieve initial count of registered users.
    $count_initial = count($user_storage->loadMultiple());

    // Verify that a spam user registration is blocked.
    $this->drupalGet('user/register');
    $this->assertNoCaptchaField();
    $edit = [
      'name' => 'spam',
      'mail' => 'spam@example.com',
    ];
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    $this->assertNoCaptchaField();
    $this->assertText(self::SPAM_MESSAGE);
    $count_new = count($user_storage->loadMultiple());
    $this->assertEqual($count_initial, $count_new, 'Existing user count found.');
    $this->assertFalse(user_load_by_name($edit['name']), 'New user not found.');

    // Verify that a unsure registration triggers no CAPTCHA and requires approval.
    $this->drupalGet('user/register');
    $this->assertNoCaptchaField();
    $edit = [
      'name' => 'unsure',
      'mail' => 'unsure@example.com',
    ];
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    $this->assertNoCaptchaField();
    $this->assertText(t('Your account is currently pending approval by the site administrator.'));
    /* @var $account \Drupal\user\Entity\User */
    $account = user_load_by_name($edit['name']);
    $this->assertTrue($account, 'New user found after solving CAPTCHA.');
    $this->assertFalse($account->isActive(), 'New user account is pending approval.');
    $data = $this->assertMollomData('user', $account->id());
    $this->assertSame('$data->moderate', $data->moderate, 1);

    // Verify that the user account is deleted after reporting it as spam.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $edit = array(
      'user_cancel_method' => 'user_cancel_delete',
      'mollom[feedback]' => 'spam',
    );
    $this->drupalPostForm(NULL, $edit, t('Cancel account'));
    $user_storage->resetCache();
    $account = $user_storage->load($account->id());
    $this->assertFalse($account, 'Reported user account not found.');
  }

  function testUserRegisterAnalysisFields() {
    $this->addPermissionsToAdmin(['administer user fields', 'administer user form display', 'administer user display', 'administer users', 'administer account settings']);
    $this->drupalLogin($this->adminUser);
    // Add additional fields to the user profile.
    $types = ['text', 'string_long', 'text_with_summary', 'text_long', 'string', 'email'];
    $fields = [];
    foreach($types as $type) {
      $field_name = Unicode::strtolower($this->randomMachineName());
      $fields[$type] = $field_name;
      entity_create('field_storage_config', array(
        'field_name' => $field_name,
        'entity_type' => 'user',
        'type' => $type,
      ))->save();

      entity_create('field_config', array(
        'field_name' => $field_name,
        'entity_type' => 'user',
        'label' => $field_name,
        'bundle' => 'user',
        'required' => FALSE,
      ))->save();

      entity_get_form_display('user', 'user', 'default')
        ->setComponent($field_name)
        ->save();
    }
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Enable text analysis protection for user registration form.
    $this->setProtectionUI('user_register_form', FormInterface::MOLLOM_MODE_ANALYSIS);
    $this->drupalLogout();

    // Test each supported field separately.
    $name = 'unsure';
    foreach ($fields as $type => $field_name) {
      $form_field_name = $field_name . '[0][value]';
      $this->drupalGet('user/register');
      $this->assertNoCaptchaField();

      $edit = array(
        'name' => $name,
        'mail' => $field_name . '@example.com',
        $form_field_name => $type === 'email' ? 'unsure@example.com' : 'unsure',
      );
      $this->drupalPostForm(NULL, $edit, t('Create new account'));
      $this->assertCaptchaField();
    }

    $this->postCorrectCaptcha(NULL, [], t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'));
    $this->assertTrue(user_load_by_name($name), t('New user was found in database.'));
  }
}
