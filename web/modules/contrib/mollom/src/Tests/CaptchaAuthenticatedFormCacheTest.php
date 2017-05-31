<?php

namespace Drupal\mollom\Tests;

/**
 * Tests CAPTCHA as authenticated user with enabled form cache.
 *
 * @group mollom
 */
class CaptchaAuthenticatedFormCacheTest extends CaptchaFormCacheTest {
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateuser([]));
  }
}
