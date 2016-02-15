<?php
/**
 * @file contains Drupal\mollom\Tests\ResponseLocalTest
 */

namespace Drupal\mollom\Tests;

/**
 * Tests that local fake Mollom server responses match expectations.
 * @group mollom
 */
class ResponseLocalTest extends ResponseTest {
  protected $useLocal = TRUE;
}
