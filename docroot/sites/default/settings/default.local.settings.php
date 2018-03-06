<?php

// Your local database connection.
$databases['default']['default'] = array(
  'database' => 'SOMETHING',
  'username' => 'SOMETHING',
  'password' => 'SOMETHING',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

// Connection to old D6 version. (If you want to test migrations.)
// Get the actual DB here: https://github.com/Drupalcz/drupalcz_db
$databases['migrate']['default'] = array(
  'database' => 'SOMETHING',
  'username' => 'SOMETHING',
  'password' => 'SOMETHING',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

/**
 * Access control for update.php script.
 */
$settings['update_free_access'] = TRUE;

/**
 * Enable local development services.
 */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

/**
 * Show all error messages, with backtrace information.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Allow test modules and themes to be installed.
 *
 * Drupal ignores test modules and themes by default for performance reasons.
 * During development it can be useful to install test extensions for debugging
 * purposes.
 */
$settings['extension_discovery_scan_tests'] = TRUE;

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
$settings['rebuild_access'] = TRUE;

/**
 * Set active config split.
 */
$config['config_split.config_split.dev']['status'] = TRUE;
//$config['config_split.config_split.prod']['status'] = TRUE;
