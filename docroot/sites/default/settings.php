<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

/**
 * Access control for update.php script.
 */
$update_free_access = FALSE;

/**
 * Salt for one-time login links and cancel links, form tokens, etc.
 */
$drupal_hash_salt = 'ISErauFvUfyYjm9n-p_X574m9tW_a0vDMmEcEsdWTHI';

/**
 * PHP settings:
 */
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cookie_lifetime', 2000000);

/**
 * Fast 404 pages:
 */
$conf['404_fast_paths_exclude'] = '/\/(?:styles)\//';
$conf['404_fast_paths'] = '/\.(?:txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
$conf['404_fast_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';

// Host
$host = $_SERVER['HTTP_HOST'];

// Fix for varnish.
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
  $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $_SERVER['HTTPS'] = 'on';
}

// Get protocol.
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
  $prefix = 'https://';
}
else {
  $prefix = 'http://';
}

// Set base URL.
$base_url = $prefix . $host;


// On Acquia Cloud, this include file configures Drupal to use the correct
// database in each site environment (Dev, Stage, or Prod). To use this
// settings.php for development on your local workstation, set $db_url
// (Drupal 5 or 6) or $databases (Drupal 7 or 8) as described in comments above.
if (file_exists('/var/www/site-php')) {
  require('/var/www/site-php/drupalcz/drupalcz-settings.inc');
}

// Acquia: dev, test, prod
if (isset($_ENV['AH_SITE_ENVIRONMENT'])) {
  $env = $_ENV['AH_SITE_ENVIRONMENT'];
  $path = DRUPAL_ROOT . "/sites/all/config/settings.{$env}.php";
}

// Load settings.
if (!empty($path) && file_exists($path)) {
  require($path);
}

/**
 * Local settings.
 * Always loaded last. This included file is not in the repo so you need to
 * create it yourself by making copy of
 * docroot/sites/all/config/default.settings.local.php and adding your config
 * there. .gitignore will make sure it will not be commited.
 */
$settings_file = DRUPAL_ROOT . "/sites/all/config/settings.local.php";
if (file_exists($settings_file)) {
  require($settings_file);
}
