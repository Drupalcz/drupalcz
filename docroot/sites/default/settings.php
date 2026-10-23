<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

require DRUPAL_ROOT . "/sites/default/settings/includes.settings.php";

// Automatically generated include for settings managed by ddev.
$ddev_settings = __DIR__ . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}
