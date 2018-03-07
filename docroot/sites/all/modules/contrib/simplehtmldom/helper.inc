<?php
/**
 * @file
 * Helper functions.
 */

/**
 * Returns path of simplhtmldom library.
 *
 * @return bool|string
 *   Path to the library.
 */
function _simplhtmldom_get_library_path() {

  $file = 'simple_html_dom.php';
  $library = 'simplehtmldom';

  // Support libraries module.
  if (module_exists('libraries') && function_exists('libraries_get_path')) {
    $library_path = libraries_get_path($library) . "/$file";
    if (file_exists($library_path)) {
      return $library_path;
    }
  }
  else {
    $paths = array(
      'sites/all/libraries/' . $library,
      drupal_get_path('module', 'simplehtmldom') . '/' . $library,
      drupal_get_path('module', 'simplehtmldom') . "/libraries",
      'profiles/' . variable_get('install_profile', 'default') . '/libraries/' . $library,
    );
    foreach ($paths as $library_dir) {
      $library_path = $library_dir . "/$file";
      if (file_exists($library_path)) {
        return $library_path;
      }
    }
  }
  return FALSE;
}
