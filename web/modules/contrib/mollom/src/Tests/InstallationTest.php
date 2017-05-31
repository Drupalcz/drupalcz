<?php

namespace Drupal\mollom\Tests;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Tests module installation and key error handling.
 * @group mollom
 */
class InstallationTest extends MollomTestBase {
  public static $modules = ['dblog', 'node', 'comment'];

  protected $useLocal = TRUE;
  public $disableDefaultSetup = TRUE;
  protected $createKeys = FALSE;
  protected $setupMollom = FALSE;

  protected $webUser = NULL;

  function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer modules',
      'administer permissions',
    ]);
    $this->webUser = $this->drupalCreateUser([]);
  }

  /**
   * Tests status handling after installation.
   *
   * We walk through a regular installation of the Mollom module instead of using
   * setUp() to ensure that everything works as expected.
   *
   * Note: Partial error messages tested here; hence, no t().
   */
  function testInstallationProcess() {
    $message_short_invalid = t('The configured Mollom API keys are invalid.');
    $message_invalid = t('The Mollom servers could be contacted, but Mollom API keys could not be verified.');
    $message_valid = t('The services are operating correctly.');
    $message_missing = t('The Mollom API keys are not configured yet.');
    $message_server = t('The Mollom servers could not be contacted. Please make sure that your web server can make outgoing HTTP requests.');
    $message_saved = t('The configuration options have been saved.');
    $admin_message = t('Visit the Mollom settings page to configure your keys.');
    $install_message = t('Mollom installed successfully. Visit the @link to configure your keys.', [
      '@link' => t('Mollom settings page'),
    ]);

    $this->drupalLogin($this->adminUser);

    // Ensure there is no requirements error by default.
    $this->drupalGet('admin/reports/status');
    $this->clickLink('run cron manually');

    // Install the Mollom module.
    $this->drupalPostForm('admin/modules', ['modules[Acquia][mollom][enable]' => TRUE], t('Install'));
    $this->assertText($install_message);

    // Now we can add the test module for the rest of the form tests.
    \Drupal::service('module_installer')->install(['mollom_test', 'mollom_test_server']);
    $settings = \Drupal::configFactory()->getEditable('mollom.settings');
    $settings->set('log_level', RfcLogLevel::DEBUG);
    $settings->save();

    $this->resetAll();

    // Verify that forms can be submitted without valid Mollom module configuration.
    $this->drupalLogin($this->webUser);
    $edit = array(
      'title' => 'spam',
    );
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertText('Successful form submission.');

    // Assign the 'administer mollom' permission and log in a user.
    $this->drupalLogin($this->adminUser);
    $edit = array(
      \Drupal\user\RoleInterface::AUTHENTICATED_ID . '[administer mollom]' => TRUE,
    );
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Verify presence of 'empty keys' error message.
    $this->drupalGet('admin/config/content/mollom');
    $this->assertText($message_missing);
    $this->assertNoText($message_invalid);

    // Verify requirements error about missing API keys.
    $this->drupalGet('admin/reports/status');
    $this->assertText($message_missing, t('Requirements error found.'));
    $this->assertText($admin_message, t('Requirements help link found.'));

    // Configure invalid keys.
    $edit = array(
      'keys[public]' => 'the-invalid-mollom-api-key-value',
      'keys[private]' => 'the-invalid-mollom-api-key-value',
    );
    $this->drupalGet('admin/config/content/mollom/settings');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText($message_saved);
    $this->assertNoText(self::FALLBACK_MESSAGE, t('Fallback message not found.'));

    // Verify presence of 'incorrect keys' error message.
    $this->assertText($message_short_invalid);
    $this->assertNoText($message_missing);
    //$this->assertNoText($message_server);

    // Verify requirements error about invalid API keys.
    $this->drupalGet('admin/reports/status', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText($message_short_invalid);

    // Ensure unreachable servers.
    \Drupal::state()->set('mollom.testing_use_local_invalid', TRUE);

    // Verify presence of 'network error' message.
    $this->drupalGet('admin/config/content/mollom/settings', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText($message_server);
    $this->assertNoText($message_missing);
    $this->assertNoText($message_invalid);

    // Verify requirements error about network error.
    $this->drupalGet('admin/reports/status', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText($message_server);
    $this->assertNoText(self::FALLBACK_MESSAGE, t('Fallback message not found.'));

    // From here on out the watchdog errors are just a nuisance.
    $this->assertWatchdogErrors = FALSE;

    // Create a testing site on backend to have some API keys.
    \Drupal::state()->setMultiple([
      'mollom.testing_use_local' => TRUE,
      'mollom.testing_use_local_invalid' => FALSE,
    ]);
    $this->resetAll();

    $mollom = $this->getClient(TRUE);
    $mollom->createKeys();

    $response = $mollom->getSite();
    $this->assertSame('publicKey', $response['publicKey'], $mollom->publicKey);

    $edit = array(
      'keys[public]' => $mollom->publicKey,
      'keys[private]' => $mollom->privateKey,
    );
    $this->drupalPostForm('admin/config/content/mollom/settings', $edit, t('Save configuration'));
    $this->assertText($message_saved);
    $this->assertText($message_valid);
    $this->assertNoText($message_missing);
    $this->assertNoText($message_invalid);

    // Verify that deleting keys throws the correct error message again.
    $this->drupalGet('admin/config/content/mollom/settings');
    $this->assertText($message_valid);
    $edit = array(
      'keys[public]' => '',
      'keys[private]' => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText($message_saved);
    $this->assertNoText($message_valid);
    $this->assertText($message_missing);
    $this->assertNoText($message_invalid);
  }
}
