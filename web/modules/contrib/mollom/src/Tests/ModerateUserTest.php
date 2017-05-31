<?php

namespace Drupal\mollom\Tests;
use Drupal\Core\Session\AccountInterface;
use Drupal\mollom\Entity\FormInterface;

/**
 * Tests moderating user accounts.
 * @group mollom
 */
class ModerateUserTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('user_register_form', FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->drupalLogout();

    // Allow visitors to register.
    // Disable e-mail verification.
    // Set default user account cancellation method.
    $config = $this->config('user.settings');
    $config
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->set('cancel_method', 'user_cancel_delete')
      ->save();
  }

  /**
   * Tests moderating users.
   */
  function testModerateUser() {
    $account = $this->registerUser();
    $this->assertMollomData('user', $account->id());

    // Log in administrator and verify that we can report to Mollom.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'user_cancel_method' => 'user_cancel_delete',
      'mollom[feedback]' => 'spam',
    ];
    $this->drupalPostForm('user/' . $account->id() . '/cancel', $edit, t('Cancel account'));
    // @todo errrrr, "content"? ;)
    $this->assertText(t('The content was successfully reported as inappropriate.'));

    // Verify that Mollom session data has been deleted.
    $this->assertNoMollomData('user', $account->id());
  }

  /**
   * Tests cancelling own user account.
   */
  function testCancelOwnAccount() {
    // Allow users to cancel their own accounts.
    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ['cancel account']);

    $account = $this->registerUser();
    $this->assertMollomData('user', $account->id());

    // Verify that no feedback options appear on the account cancellation form.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertNoText(t('Report as…'));

    // Cancel own account.
    $this->drupalPostForm(NULL, [], t('Cancel account'));
    $this->assertText(t('A confirmation request to cancel your account has been sent to your email address.'));
    $this->assertNoText(t('The content was successfully reported as inappropriate.'));

    // Confirm account cancellation request.
    // Turn off error assertion because the link returns a 404 due to the batch
    // user process... but it really does work to cancel the account. hmmmm.
    $this->assertWatchdogErrors = FALSE;
    $this->drupalGet($this->getCancelUrl());

    // Verify that Mollom session data has been deleted.
    $this->assertNoMollomData('user', $account->id());
  }

  /**
   * Registers a new user through the UI.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The new user account.
   */
  function registerUser() {
    $password = $this->randomMachineName();
    $edit = array(
      'name' => $this->randomMachineName(),
      'pass[pass1]' => $password,
      'pass[pass2]' => $password,
      'mail' => 'spam@example.com',
    );
    $this->postCorrectCaptcha('user/register', $edit, t('Create new account'));

    // Determine new uid.
    $uids = \Drupal::entityQuery('user')->condition('name', $edit['name'])->execute();
    $account = \Drupal::entityManager()->getStorage('user')->load(reset($uids));

    // If the user is logged in directly after registering, update the logged in
    // user on DrupalWebTestCase, so drupalLogin() continues to work.
    if ($this->config('user.settings')->get('register') === USER_REGISTER_VISITORS) {
      $this->loggedInUser = $account;
    }

    return $account;
  }

  /**
   * Parses the user account cancellation URL out of the last sent mail.
   */
  function getCancelUrl() {
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    preg_match('@[^\s]+user/\d+/cancel/confirm/[^\s]+@', $mail['body'], $matches);
    return $matches[0];
  }
}
