<?php

namespace Drupal\mollom\Tests;

/**
 * Tests text analysis of an authenticated user with enabled form cache.
 * @group mollom
 */
class AnalysisAuthenticatedFormCacheTest extends AnalysisFormCacheTest {

  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([]));
  }
}
