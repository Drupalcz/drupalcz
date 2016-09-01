<?php

/**
 * @file
 * Test for the Drop Guard settings page.
 */

namespace Drupal\dropguard\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Drop Guard settings form functionality
 *
 * @group dropguard
 */
class SettingsFormTest extends WebTestBase {

  /**
   * Install Drop Guard module.
   *
   * @var array
   */
  public static $modules = array('dropguard');

  /**
   * A user with 'administer site configuration' permission.
   */
  private $user;

  /**
   * Perform initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(array('administer site configuration'));
  }

  /**
   * Tests that the 'admin/config/services/dropguard' path
   * returns the right content.
   */
  public function testSettingsFormExists() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/services/dropguard');
    $this->assertResponse(200);

    $this->assertText('Drop Guard settings', 'Settings form is shown.');
  }
}
