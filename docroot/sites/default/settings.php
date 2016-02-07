<?php
/**
 * @file
 * Drupal site-specific configuration file.
 */

/**
 * Access control for update.php script.
 */
$settings['update_free_access'] = FALSE;

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 */
$settings['hash_salt'] = '7yYcmIWkPnXwJwkR7_efzJqfGP4L8MvC-_4Gac27A2YElqLxLQVn_9vDpWMatIrWWgx-BgenWA';

/**
 * Install profile.
 */
$settings['install_profile'] = 'dcz';

/**
 * Domain redirects.
 */
$aliases = array(
  'http://drupal.cz' => 'http://www.drupal.cz',
);

/**
 * Varnish.
 */
$protocol = 'http://';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
  $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' &&
  isset($_SERVER['REMOTE_ADDR']) &&
  strpos($_SERVER['REMOTE_ADDR'], '10.') === 0
) {
  $_SERVER['HTTPS'] = 'on';
  $protocol = 'https://';
}

/**
 * Host and $base_url.
 */
$host = $_SERVER['HTTP_HOST'];
$full = $protocol . $host;
$base_url = $full;

/**
 * Domain/Alias redirects.
 */
if (!empty($aliases[$full])) {
  $domain = $aliases[$full];
  $uri = $_SERVER['REQUEST_URI'];
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: $domain$uri");
  exit();
}

/**
 * Acquia environment settings - by Acquia.
 */
if (file_exists('/var/www/site-php')) {
  // Copy of D6 db for imports.
  require '/var/www/site-php/drupalcz/d6_migration_source-settings.inc';
  // D8 db.
  require '/var/www/site-php/drupalcz/drupalcz-settings.inc';
  // Make sure D8 can run.
  Database::setActiveConnection('drupalcz');
}

/**
 * Location of the site configuration files.
 */
$config_directories = array(
  CONFIG_SYNC_DIRECTORY => "sites/default/config",
);

/**
 * Acquia - custom environment settings.
 */
if (isset($_ENV['AH_SITE_ENVIRONMENT'])) {
  $env = $_ENV['AH_SITE_ENVIRONMENT'];
  $path = DRUPAL_ROOT . "/sites/default/settings.{$env}.php";
}

// Load settings.
if (!empty($path) && file_exists($path)) {
  require $path;
}

/**
 * If there is a local settings file, then include it.
 */
$local_settings = DRUPAL_ROOT . "/sites/default/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}
