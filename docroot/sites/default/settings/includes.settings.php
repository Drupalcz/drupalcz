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
$settings['install_profile'] = 'minimal';

/**
 * For custom installation
 */
$settings['config_sync_directory'] = "../config/default";

/**
 * Set content directory for default_content_deploy.
 */
$settings['default_content_deploy_content_directory'] = '../content';

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
$prod_base_url = 'https://www.drupal.cz';
$aliases = array(
  'http://drupal.cz' => $prod_base_url,
  'http://www.drupal.cz' => $prod_base_url,
  'https://drupal.cz' => $prod_base_url,
  'http://drupalasociace.cz' => $prod_base_url,
  'https://drupalasociace.cz' => $prod_base_url,
  'http://www.drupalasociace.cz' => $prod_base_url,
  'https://www.drupalasociace.cz' => $prod_base_url,
);

/**
 * Unshielded base URLs.
 */
// Make sure redirects are not covered by shield.
$unshielded = array_keys($aliases);
// Unlock production.
$unshielded[] = $prod_base_url;

/**
 * Varnish.
 */
$protocol = 'http://';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
  $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
) {
  $_SERVER['HTTPS'] = 'on';
  $protocol = 'https://';
}

/**
 * Host and $base_url.
 */
// Travis doesn't have HTTP_HOST.
if (isset($_SERVER['HTTP_HOST'])) {
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
}

/**
 * Shield.
 */
$config['shield.settings']['allow_cli'] = TRUE;
if (isset($_SERVER['HTTP_HOST']) && !in_array($base_url, $unshielded)) {
  $config['shield.settings']['shield_enable'] = TRUE;
  $config['shield.settings']['credentials']['shield']['user'] = 'drupal';
  $config['shield.settings']['credentials']['shield']['pass'] = 'cz';
  $config['shield.settings']['print'] = 'Check out https://github.com/Drupalcz/drupalcz ;-)';
}

/**
 * Acquia - custom environment settings.
 */
if (isset($_ENV['AH_SITE_ENVIRONMENT'])) {
  $env = $_ENV['AH_SITE_ENVIRONMENT'];
  $path = DRUPAL_ROOT . "/sites/default/settings/ac.{$env}.settings.php";

  // Load secure variables.
  // @See: https://docs.acquia.com/acquia-cloud/manage/files/system-files/private
  if (file_exists("/mnt/gfs/drupalcz.$env/nobackup/secure.php")) {
    require "/mnt/gfs/drupalcz.$env/nobackup/secure.php";
  }
  // Populate secure variables.
  $config['slack_invite.settings']['token'] = getenv('SLACK_TOKEN');

  // Database credentials.
  if (file_exists('/var/www/site-php')) {
    require '/var/www/site-php/drupalcz/drupalcz-settings.inc';
  }
}

// Load settings.
if (!empty($path) && file_exists($path)) {
  require $path;
}

/**
 * Lando environment settings.
 */
if (isset($_ENV["LANDO_APP_NAME"])) {
  $path = DRUPAL_ROOT . "/sites/default/settings/lando.settings.php";
  // Load settings.
  if (!empty($path) && file_exists($path)) {
    require $path;
  }
}

/**
 * Allow final local override.
 */
$path = DRUPAL_ROOT . "/sites/default/settings/local.settings.php";
if (!empty($path) && file_exists($path)) {
  require $path;
}
