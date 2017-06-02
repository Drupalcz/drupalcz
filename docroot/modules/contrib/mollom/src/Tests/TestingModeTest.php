<?php

namespace Drupal\mollom\Tests;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\mollom\Entity\FormInterface;

/**
 * Tests toggling of testing mode.
 * @group mollom
 */
class TestingModeTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  /**
   * Overrides MollomWebTestCase::$mollomClass.
   *
   * In order to test toggling of the testing mode, ensure the regular class for
   * production usage is used.
   */
  protected $mollomClass = 'MollomDrupal';

  /**
   * Prevent automated setup of testing keys.
   */
  public $disableDefaultSetup = TRUE;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  function setUp() {
    parent::setUp();
    $this->settings = \Drupal::configFactory()->getEditable('mollom.settings');
    $this->settings->set('test_mode.enabled', FALSE)->save();
    $this->getClient(TRUE);

    // Enable testing mode warnings.
    \Drupal::state()->set('mollom.omit_warning', FALSE);

    $this->adminUser = $this->drupalCreateUser(array(
      'access administration pages',
      'administer mollom',
    ));
  }

  /**
   * Tests enabling and disabling of testing mode.
   */
  function testTestingMode() {
    $this->drupalLogin($this->adminUser);

    // Protect mollom_test_form.
    $this->setProtectionUI('mollom_test_post_form', FormInterface::MOLLOM_MODE_ANALYSIS);
    $this->settings->set('fallback', FormInterface::MOLLOM_FALLBACK_ACCEPT)->save();

    // Setup production API keys and expected languages. They must be retained.
    $publicKey = 'the-invalid-mollom-api-key-value';
    $privateKey = 'the-invalid-mollom-api-key-value';
    $expectedLanguages = ['en','de'];
    $edit = [
      'keys[public]' => $publicKey,
      'keys[private]' => $privateKey,
      'languages_expected[]' => $expectedLanguages,
    ];
    $this->drupalGet('admin/config/content/mollom/settings');
    $this->assertText('The Mollom API keys are not configured yet.');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertNoText('The Mollom API keys are not configured yet.');
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertText('The configured Mollom API keys are invalid.');

    $this->drupalLogout();

    // Verify that spam can be posted, since testing mode is disabled and API
    // keys are invalid.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'spam',
    ];
    $this->drupalGet('mollom-test/form', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->drupalPostForm(NULL, $edit, t('Save'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText('Successful form submission.');

    // Enable testing mode.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'testing_mode' => TRUE,
    ];
    $this->drupalGet('admin/config/content/mollom/settings', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText('The configured Mollom API keys are invalid.');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertNoText('The Mollom API keys are not configured yet.');
    $this->assertNoText('The configured Mollom API keys are invalid.');
    $this->assertText(t('Mollom testing mode is still enabled.'));
    // Verify that expected languages were retained.
    foreach($expectedLanguages as $lang) {
      $this->assertOptionSelected('edit-languages-expected', $lang);
    }

    $this->drupalLogout();

    // Verify presence of testing mode warning.
    $this->drupalGet('mollom-test/form');
    /*
     * There is a problem with the way #lazy_builder is handling the status
     * messages in tests.  As a result, the text is only output when a message
     * is set before it too.... further investigation is needed and possibly
     * a bug filed.
    $this->assertText(t('Mollom testing mode is still enabled.'));
    */

    // Verify that no spam can be posted with testing mode enabled.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'spam',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(self::SPAM_MESSAGE);
    $this->assertNoText('Successful form submission.');

    // Disable testing mode.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/mollom/settings');
    $this->assertText('Mollom testing mode is still enabled.');
    $edit = [
      'testing_mode' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertText('The configured Mollom API keys are invalid.');
    $this->assertNoText('Mollom testing mode is still enabled.');

    // Verify that production API keys still exist.
    $this->assertFieldByName('keys[public]', $publicKey);
    $this->assertFieldByName('keys[private]', $privateKey);
    foreach($expectedLanguages as $lang) {
      $this->assertOptionSelected('edit-languages-expected', $lang);
    }
  }
}
