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
 * Acquia - custom environment settings.
 */
if (isset($_ENV['AH_SITE_ENVIRONMENT'])) {
  $env = $_ENV['AH_SITE_ENVIRONMENT'];
  $path = DRUPAL_ROOT . "/sites/default/settings/ac.{$env}.settings.php";
}

// Load settings.
if (!empty($path) && file_exists($path)) {
  require $path;
}
