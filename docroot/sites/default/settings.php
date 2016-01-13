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
  require '/var/www/site-php/drupalcz/drupalcz-settings.inc';
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

/**
 * Password protect the site with a single function.
 *
 * @See: http://mc-kenna.com/drupal/2012/04/simple-http-authentication-for-drupal-sites-updated
 *
 * @ToDo: Replace with contrib module once stable release is available.
 */
function secure_the_site_please($username = 'drupal', $password = 'cz', $message = "Username: drupal Password: cz") {
  // Password protect this site but ignore drush and other command-line
  // environments.
  if (php_sapi_name() != 'cli') {
    // PHP-cgi fix.
    $a = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6));
    if ((strlen($a) == 0) || (strcasecmp($a, ":") == 0)) {
      header('WWW-Authenticate: Basic realm="Private"');
      header('HTTP/1.0 401 Unauthorized');
    }
    else {
      list($entered_username, $entered_password) = explode(':', $a);
      $_SERVER['PHP_AUTH_USER'] = $entered_username;
      $_SERVER['PHP_AUTH_PW'] = $entered_password;
    }
    if (!(isset($_SERVER['PHP_AUTH_USER']) && ($_SERVER['PHP_AUTH_USER'] == $username && $_SERVER['PHP_AUTH_PW'] == $password))) {
      header('WWW-Authenticate: Basic realm="' . $message . '"');
      header('HTTP/1.0 401 Unauthorized');
      // Fallback message when the user presses cancel / escape.
      echo 'Access denied';
      exit;
    }
  }
}
