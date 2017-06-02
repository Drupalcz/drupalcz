<?php

namespace Drupal\mollom\Tests;

/**
 * Tests that a configured endpoint behaves the same as specified endpoints.
 * @group mollom
 */
class ResponseConfiguredTest extends MollomTestBase {

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server'];

  function testConfiguredUrl() {
    // Set the configured URL to be the same as the test server endpoing.
    $test_url = $this->getAbsoluteURL('mollom-test/rest');
    $stripped = preg_replace('/^[a-z]+:\\/\\//i', '', $test_url);
    \Drupal::configFactory()->getEditable('mollom.settings')->set('test_mode.api_endpoint', $stripped)->save();

    // Clear out Mollom local variable so that it can be instantiated with the
    // correct endpoint.
    $this->getClient(TRUE);

    // The DrupalTest class should now use the local test server.
    $this->setKeys();
    $this->assertValidKeys();

    // Check the watchdog from the key assertion is using the correct server.
    // If the keys passed assertion, then they were created on the right server
    // too.
    foreach ($this->messages as $row) {
      $this->assertTrue(strpos($row->variables, 'Request: POST ' . $test_url . '/v1/site/') !== FALSE, 'Keys verified from configured endpoint.');
    }
  }
}
