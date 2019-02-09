<?php

/**
 * @file
 * Contains apd_membership.page.inc.
 *
 * Page callback for APD membership entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for APD membership templates.
 *
 * Default template: apd_membership.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_apd_membership(array &$variables) {
  // Fetch ApdMembership Entity Object.
  $apd_membership = $variables['elements']['#apd_membership'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
