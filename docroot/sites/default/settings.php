<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

/**
 * Install profile.
 *
 * It needs to be here. Otherwise install process edits this file.
 */
$settings['install_profile'] = 'dcz';

// BLT will setup lots of things for us.
// @See:
require DRUPAL_ROOT . "/../vendor/acquia/blt/settings/blt.settings.php";
