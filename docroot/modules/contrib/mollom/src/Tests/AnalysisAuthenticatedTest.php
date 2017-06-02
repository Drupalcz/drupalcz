<?php

namespace Drupal\mollom\Tests;

/**
 * Tests text analysis as authenticated user.
 * @group mollom
 */
class AnalysisAuthenticatedTest extends AnalysisTest {
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([]));
  }
}
