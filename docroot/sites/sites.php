<?php

/**
 * @file
 * Configuration file for multi-site support and directory aliasing feature.
 */

// This file is loaded multiple times when using Drush/Drupal Console.
// Prevent "Cannot redeclare..." error.
if (!function_exists("secure_the_site_please")) {
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
}
