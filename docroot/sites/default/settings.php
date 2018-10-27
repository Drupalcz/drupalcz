<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

// Workaround for "Undefined index: HTTP_HOST" on Travis.
// @See: https://github.com/acquia/blt/issues/2527#issuecomment-375690543
$_SERVER['PWD']=DRUPAL_ROOT;

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
