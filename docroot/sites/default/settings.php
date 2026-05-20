<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

require_once DRUPAL_ROOT . "/sites/default/settings/includes.settings.php";

// Automatically generated include for settings managed by ddev.
$ddev_settings = __DIR__ . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}

// WTF, without this here it doesnt work.. to be fixed!
$settings['config_sync_directory'] = "../config/default";
