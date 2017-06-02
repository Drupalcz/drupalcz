<?php

namespace Drupal\mollom\Tests;

/**
 * Tests CAPTCHA as authenticated user.
 *
 * @group mollom
 */
class CaptchaAuthenticatedTest extends CaptchaTest {
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([]));
  }
}
