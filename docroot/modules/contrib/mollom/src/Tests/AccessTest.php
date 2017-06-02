<?php

namespace Drupal\mollom\Tests;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Confirm that there is a working key pair and that this status is correctly
 * indicated on the module settings page for appropriate users.
 *
 * @group mollom
 */
class AccessTest extends MollomTestBase {

  const MESSAGE_KEY_LENGTH = 'Keys must be 32 characters long.  Ensure you copied the key correctly.';
  const MESSAGE_SAVED = 'The configuration options have been saved.';
  const MESSAGE_INVALID = 'The configured Mollom API keys are invalid.';
  const MESSAGE_NOT_CONFIGURED = 'The Mollom API keys are not configured yet.';

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server'];

  protected $createKeys = FALSE;
  protected $useLocal = TRUE;

  function setUp() {
    parent::setUp();
    \Drupal::configFactory()->getEditable('mollom.settings')->set('test_mode.enabled', FALSE)->save();
  }

  /**
   * Configure an invalid key pair and ensure error message.
   */
  function testKeyPairs() {
    // No error message or watchdog messages should be thrown with default
    // testing keys.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/mollom/settings');

    $this->assertText(self::MESSAGE_NOT_CONFIGURED);
    $this->assertNoText(t(self::MESSAGE_KEY_LENGTH));
    $this->assertNoText(t(self::MESSAGE_SAVED));
    $this->assertNoText(t(self::MESSAGE_INVALID));

    // Try to setup completely invalid keys.
    $edit = array(
      'keys[public]' => 'foo',
      'keys[private]' => 'bar',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText(t(self::MESSAGE_KEY_LENGTH));
    $this->assertNoText(t(self::MESSAGE_SAVED));
    $this->assertNoText(t(self::MESSAGE_INVALID));
    $this->assertNoText(t(self::MESSAGE_NOT_CONFIGURED));

    // Set up invalid test keys and check that an error message is shown.
    $edit = array(
      'keys[public]' => 'the-invalid-mollom-api-key-value',
      'keys[private]' => 'the-invalid-mollom-api-key-value',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'), array('watchdog' => RfcLogLevel::EMERGENCY));
    $this->assertText(t(self::MESSAGE_SAVED));
    $this->assertText(t(self::MESSAGE_INVALID));
    $this->assertNoText(t(self::MESSAGE_KEY_LENGTH));
    $this->assertNoText(t(self::MESSAGE_NOT_CONFIGURED));
  }

  /**
   * Make sure that the Mollom settings page works for users with the
   * 'administer mollom' permission but not those without
   * it.
   */
  function testAdminAccessRights() {
    // Check access for a user that only has access to the 'administer
    // site configuration' permission. This user should have access to
    // the Mollom settings page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/mollom');
    $this->assertResponse(200);

    // Check access for a user that has everything except the 'administer
    // mollom' permission. This user should not have access to the Mollom
    // settings page.
    $web_user = $this->drupalCreateUser(array_diff(\Drupal::moduleHandler()->invokeAll('perm'), array('administer mollom')));
    $this->drupalLogin($web_user);
    $this->drupalGet('admin/config/content/mollom', array('watchdog' => RfcLogLevel::WARNING));
    $this->assertResponse(403);
  }
} 
