<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

// BLT will setup lots of things for us.
// @See: docroot/sites/default/settings/includes.settings.php
require DRUPAL_ROOT . "/../vendor/acquia/blt/settings/blt.settings.php";

/**
 * Install profile.
 *
 * It needs to be here. Otherwise install process edits this file.
 */
$settings['install_profile'] = 'minimal';

/**
 * For custom installation
 */
$config_directories['sync'] = "../config/default";
