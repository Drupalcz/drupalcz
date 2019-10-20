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
  $config['shield.settings']['credentials']['shield']['user'] = 'drupal';
  $config['shield.settings']['credentials']['shield']['pass'] = 'cz';
  $config['shield.settings']['print'] = 'Check out https://github.com/Drupalcz/drupalcz ;-)';
}


// Custom Travis CI settings.
$travis_settings = DRUPAL_ROOT . "/sites/default/settings/travis.settings.php";
if (getenv('TRAVIS') && file_exists($travis_settings)) {
  require $travis_settings;
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
  $config['cleantalk.settings']['cleantalk_authkey'] = getenv('CLEANTALK_KEY');
}

// Load settings.
if (!empty($path) && file_exists($path)) {
  require $path;
}
