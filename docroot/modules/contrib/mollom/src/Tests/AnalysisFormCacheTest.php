<?php

namespace Drupal\mollom\Tests;

/**
 * Tests text analysis with enabled form cache.
 * @group mollom
 */
class AnalysisFormCacheTest extends AnalysisTest {

  public function setUp() {
    parent::setUp();
    \Drupal::state()->set('mollom_test.cache_form', TRUE);

    // Prime the form cache.
    $this->drupalGet('mollom-test/form');
    $this->assertText('Views: 0');
    $edit = [
      'title' => $this->randomString()
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }
}
