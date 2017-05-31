<?php

namespace Drupal\mollom\Tests;

/**
 * Tests CAPTCHA with enabled form cache.
 *
 * @group mollom
 */
class CaptchaFormCacheTest extends CaptchaTest {
  public function setUp() {
    parent::setUp();
    \Drupal::state()->set('mollom_test.cache_form', TRUE);

    // Prime the form cache.
    $this->drupalGet('mollom-test/form');
    $this->assertText('Views: 0');
    $edit = [
      'title' => $this->randomString(),
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }
}
