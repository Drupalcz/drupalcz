<?php

namespace Drupal\mollom\Tests;

/**
 * Tests that local fake Mollom server responses match expectations.
 * @group mollom
 */
class ResponseLocalTest extends ResponseTest {
  protected $useLocal = TRUE;
}
