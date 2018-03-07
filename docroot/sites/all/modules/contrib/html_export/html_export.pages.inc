<?php
/**
 * @file
 * Batch processing and heavy lifting associated with export
 */

/**
 * Helper function to start the HTML rendering job
 */
function _html_export_job_page() {
  $form = array();
  $path_provders = module_invoke_all('html_export_add_path_provider');
  // allow projects to alter list
  drupal_alter('html_export_add_path_provider', $path_provders);
  // convert providers to options array
  foreach ($path_provders as $key => $provider) {
    $options[$key] = t($provider['title']);
  }
  // build the form
  $form['path_selector'] = array(
    '#type' => 'select',
    '#multiple' => TRUE,
    '#title' => t('Path selectors'),
    '#description' => t('Select paths to combine in formulating a list of paths in which to use for export.'),
    '#options' => $options,
    '#required' => TRUE,
  );
  $form['html_export_other_paths'] = array(
    '#type' => 'textarea',
    '#title' => t('Other paths to export'),
    '#description' => t("Provide one path per line of additional paths to export."),
    '#default_value' => variable_get('html_export_other_paths', '<front>'),
  );
  $form['html_export_dom_remove'] = array(
    '#type' => 'textarea',
    '#title' => t('Advanced dom removal'),
    '#description' => t("Supply a css style selector to target the removal of. This is especially useful for removing things like the admin toolbar, tabs, or other elements you don't want to have exported. Add one selector per line."),
    '#default_value' => variable_get('html_export_dom_remove', ''),
  );
  // format for dealing with the export
  $formats = module_invoke_all('html_export_add_format');
  // allow projects to alter list
  drupal_alter('html_export_add_format', $formats);
  // convert providers to options array
  foreach ($formats as $key => $format) {
    $formats_ary[$key] = t($format['option']);
  }
  $form['html_export_format'] = array(
    '#type' => 'select',
    '#title' => t('Packaging format'),
    '#description' => t('What would you like to do with your format?'),
    '#options' => $formats_ary,
    '#required' => TRUE,
  );
  $form['render_errors'] = array(
    '#type' => 'checkbox',
    '#default_value' => TRUE,
    '#title' => t("Don't export paths returning errors"),
    '#description' => t("If a path request returns a 403, 404 or 500 error then don't export that page."),
  );
  $form['anonymous'] = array(
    '#type' => 'checkbox',
    '#default_value' => TRUE,
    '#title' => t('Render as anonymous'),
    '#description' => t('Render pages via an external request so that they are treated as anonymous.'),
  );
  // enable ability to export as if you are another user
  if (module_exists('masquerade')) {
    if (masquerade_access('autocomplete')) {
      $markup_value .= t('Enter the username to masquerade as.');
      $form['masquerade_user_field'] = array(
        '#type' => 'textfield',
        '#title' => t('EXPERIMENTAL: Masquerade for export'),
        '#description' => t('Select a user account to masquerade as during this export. This allows you to render to file as if you were a different user. This has known page rendering conflicts with certain projects, anonymous is recommended for an accurate export.'),
        '#size' => '18',
        '#default_value' => '',
        '#autocomplete_path' => 'masquerade/autocomplete',
        '#required' => FALSE,
      );
    }
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Render as html'),
  );
  return $form;
}

/**
 * Submit handler for HTML rendering job
 */
function _html_export_job_page_submit($form, &$form_state) {
  // invoke the hooks that will provide us with a list of paths
  $path_provders = module_invoke_all('html_export_add_path_provider');
  // allow projects to alter list
  drupal_alter('html_export_add_path_provider', $path_provders);
  // use the selector from the previous form as to what paths to gather
  $paths = array();
  // account for additional paths added manually
  if (!empty($form_state['values']['html_export_other_paths'])) {
    $other_paths = $form_state['values']['html_export_other_paths'];
    // convert <front> to an actual path if set
    if (strpos($other_paths, '<front>') !== FALSE) {
      $front_found = TRUE;
      $other_paths = str_replace('<front>', variable_get('site_frontpage', 'node'), $other_paths);
    }
    else {
      $front_found = FALSE;
    }
    $other_paths = str_replace("\r", "", $other_paths);
    $paths = explode("\n", $other_paths);
  }
  $uid = NULL;
  $selectors = $form_state['values']['path_selector'];
  foreach ($selectors as $selector) {
    // only submit arg if its set to avoid notices
    if (isset($path_provders[$selector]['arg'])) {
      $tmp = call_user_func_array($path_provders[$selector]['callback'], array($path_provders[$selector]['arg']));
    }
    else {
      $tmp = call_user_func_array($path_provders[$selector]['callback'], array());
    }
    $paths = array_merge($paths, $tmp);
    // make sure all paths are numeric as spec won't be
    sort($paths);
    // reverse order so that deeper paths of nodes are rendered
    $paths = array_reverse($paths);
  }
  // bring along advanced dom removal on publish, accounting for multiple
  $form_state['values']['html_export_dom_remove'] = str_replace("\r", "", $form_state['values']['html_export_dom_remove']);
  $dom_remove = explode("\n", $form_state['values']['html_export_dom_remove']);
  $format = $form_state['values']['html_export_format'];
  // block pages that return errors from export
  if (!empty($form_state['values']['render_errors'])) {
    $render_errors = FALSE;
  }
  else {
    $render_errors = TRUE;
  }
  // see if checkbox for anonymous was set
  if (!empty($form_state['values']['anonymous'])) {
    $uid = 0;
  }
  else {
    // see if masquerade was enabled and used
    if (!empty($form_state['values']['masquerade_user_field'])) {
      // convert name we were passed to uid
      $masq_user = user_load(array('name' => $form_state['values']['masquerade_user_field']));
      if (isset($masq_user->uid)) {
        $uid = $masq_user->uid;
      }
    }
  }

  html_export_process_job($paths, 'export'. time(), $dom_remove, $uid, $front_found, $render_errors, $format);
}

/**
 * Initialize batch job
 */
function html_export_process_job($paths, $export_folder, $dom_remove = NULL, $uid = NULL, $front_found = FALSE, $render_errors = FALSE, $format = '') {
  $GLOBALS['html_export_active'] = TRUE;
  $anonymous = FALSE;
  if ($uid != NULL) {
    masquerade_switch_user($uid);
  }
  elseif ($uid == 0) {
    $anonymous = TRUE;
  }
  // create a folders
  $dir = file_create_path(file_directory_path() .'/html_export');
  file_check_directory($dir, 1);
  // make the directory path for this export (just timestamp)
  $export_path = $dir .'/'. $export_folder;
  file_check_directory(file_create_path($export_path), 1);
  // render pages and deep copy directories
  $batch = array(
    'operations' => array(
      array('html_export_render_pages', array($paths, $export_path, $dom_remove, $anonymous, $front_found, $render_errors)),
      array('html_export_copy_all_resources', array($export_path)),
      array('html_export_cleanup', array($export_path, $uid, $format)),
      ),
    'finished' => 'html_export_finished',
    'title' => t('Processing html export'),
    'init_message' => t('HTML export is starting.'),
    'progress_message' => t('Processed @current out of @total.'),
    'error_message' => t('HTML export has encountered an error.'),
    'file' => drupal_get_path('module', 'html_export') . '/html_export.pages.inc',
  );
  batch_set($batch);
  batch_process('html_export');
}

/**
 * Batch Operation Callback to render all pages
 */
function html_export_render_pages($paths, $export_path, $dom_remove, $anonymous, $front_found, $render_errors, &$context) {
  // first run
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($paths);
    $context['results']['paths'] = array();
    $context['results']['asset_list'] = array();
    // if we found front then render it first
    if ($front_found) {
      $front = variable_get('site_frontpage', 'node');
      // allow custom environments to modify front page definition
      drupal_alter('html_export_front', $front);
      $data = _html_export_get_page_data($front, $anonymous, $render_errors);
       // locate all the resources used to make this page and copy them
      _html_export_copy_resources($data, $export_path, $context);
      // special alter that doesn't rewrite base path
      drupal_alter('html_export_data', $data, $paths, $front, $dom_remove, FALSE);
      // open a writable file at that location
      if ($file = fopen($export_path .'/index.html' , 'w')) {
        fwrite($file, $data);
        fclose($file);
      }
    }
  }
  // process limit amount of records
  $count = 0;
  while ($count<HTML_EXPORT_PROCESS_LIMIT && $context['sandbox']['progress'] < $context['sandbox']['max']) {
    $active = $context['sandbox']['progress'];
    $context['message'] = t('Now processing %path', array('%path' => drupal_get_path_alias($paths[$active])));
    // Store some result for post-processing in the finished callback.
    $context['results']['paths'][] = $paths[$active];
    $data = _html_export_get_page_data($paths[$active], $anonymous, $render_errors);
    // locate all the resources used to make this page and copy them
    _html_export_copy_resources($data, $export_path, $context);
    // allow projects to process page data
    drupal_alter('html_export_data', $data, $paths, $paths[$active], $dom_remove);
    // prepare to save file by creating folders for path
    $tmp = split('/', $paths[$active]);
    $folder_path = '';
    // build path leading up to the current one
    foreach ($tmp as $folder) {
      $folder_path .= '/'. $folder;
      $tmppath = file_create_path($export_path . $folder_path);
      // ensure we can write there
      file_check_directory($tmppath, 1);
    }
    // open a writable file at that location
    if ($file = fopen($export_path .'/'. $paths[$active] .'/index.html' , 'w')) {
      fwrite($file, $data);
      fclose($file);
    }
    // Update our progress information.
    $context['sandbox']['progress']++;
    $count++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Batch Operation Callback to copy all resources
 */
function html_export_copy_all_resources($export_path, &$context) {
  // first run
  if (!isset($context['sandbox']['progress'])) {
    // allow other modules to modify the asset list to include other assets
    drupal_alter('html_export_asset_list', $context['results']['asset_list']);
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['active_copy_id'] = 0;
    $context['results']['files'] = array();
    $context['sandbox']['max'] = count($context['results']['asset_list']);
    // convert keys to array positions
    sort($context['results']['asset_list']);
    // reverse order so that deeper paths of nodes are rendered
    $context['results']['asset_list'] = array_reverse($context['results']['asset_list']);
  }
  // process limit amount of records
  $count = 0;
  $active_copy_id = $context['sandbox']['active_copy_id'];
  while ($count<HTML_EXPORT_FILE_PROCESS_LIMIT && $active_copy_id < $context['sandbox']['max']) {
    _html_export_copy_file($context['results']['asset_list'][$active_copy_id], $export_path, $context);
    // Update our progress information.
    $context['message'] = t('Now copying %file', array('%file' => $context['results']['asset_list'][$active_copy_id]));
    $active_copy_id++;
    $count++;
    $context['sandbox']['progress']++;
    $context['sandbox']['active_copy_id'] = $active_copy_id;
  }
  $tmpmsg = drupal_get_messages();
  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Batch Operation Callback to clean up before finished
 */
function html_export_cleanup($export_path, $uid, $format, &$context) {
  // unmasquerade if we are done
  if ($uid != NULL) {
    masquerade_switch_back();
  }
  // format for dealing with the export
  $formats = module_invoke_all('html_export_add_format');
  // allow projects to alter list
  drupal_alter('html_export_add_format', $formats);
  $format_data = $formats[$format];
  // pull out just the export folder location
  $tmp = explode('/', $export_path);
  $export = array_pop($tmp);
  if ($format != 'none') {
    $format_data['path'] = str_replace('%', $export .'/'. drupal_get_token(), $format_data['path']);
  }
  else {
    $format_data['path'] = str_replace('%', $export, $format_data['path']);
  }
  // message with link
  $message = l(t($format_data['text']), $format_data['path'], array('absolute' => TRUE, 'purl' => array('disabled' => TRUE)));
  drupal_set_message(filter_xss($message));
  $GLOBALS['html_export_active'] = FALSE;
}

/**
 * Helper function to deep copy all resource files
 */
function _html_export_copy_resources($data, $export_path, &$context) {
  // Create a DOM object
  $html_obj = new simple_html_dom();
  $asset_list = array();
  // Load HTML from a string
  $html_obj->load($data);
  $resources = array('link', 'img', 'script', 'a', 'object');
  // repair paths for these resources
  foreach ($resources as $find) {
    // find the selectors in the document
    $objs = $html_obj->find($find);
    // account for ability to find multiple objects
    foreach ($objs as $obj) {
      // account for internal href resources, cut arguments
      if (isset($obj->href) && strpos($obj->href, 'http://') === FALSE && strpos($obj->href, 'https://') === FALSE && strpos($obj->href, '.') !== FALSE && !in_array($obj->href, $asset_list)) {
          $href = explode('?', $obj->href);
          $asset_list[$href[0]] = $href[0];
      }
      // account for internal src resources, cut arguments
      if (isset($obj->src) && strpos($obj->src, 'http://') === FALSE && strpos($obj->src, 'https://') === FALSE && strpos($obj->src, '.') !== FALSE && !in_array($obj->src, $asset_list)) {
        $src = explode('?', $obj->src);
        $asset_list[$src[0]] = $src[0];
      }
    }
  }
  // assets to copy, need to filter out base path
  foreach ($asset_list as $key => $file) {
    // only replace first occurance as this will be base path
    $pos = strpos($file, base_path());
    if ($pos !== FALSE) {
      $context['results']['asset_list'][$key] = substr_replace($file, '', $pos, drupal_strlen(base_path()));
    }
  }
}

/**
 * Helper function to deep copy all resource files
 */
function _html_export_copy_file($source, $export_path, &$context) {
  // Simple copy for a file
  if (is_file($source)) {
    // split to build the folder structure
    $tmp = explode('/', $source);
    array_pop($tmp);
    $folder_path = '';
    $deepcopy = implode('/', $tmp);
    // build path leading up to the current one
    foreach ($tmp as $folder) {
      $folder_path .= '/'. $folder;
      $tmppath = file_create_path($export_path . $folder_path);
      // ensure we can write there
      file_check_directory($tmppath, 1);
    }
    // deep copy in each directory assembled previously ignoring files and devel
    if (strpos($deepcopy, file_directory_path()) === FALSE && strpos($deepcopy, drupal_get_path('module', 'devel')) === FALSE) {
      _html_export_deep_file_copy($deepcopy, $export_path . $folder_path, $context);
    }
    $context['results']['files'][$source] = $source;
    return copy($source, $export_path .'/'. $source);
  }
}

/**
 * Helper function to deep copy all resource files
 */
function _html_export_deep_file_copy($source, $dest, &$context) {
  // Simple copy for a file
  if (is_file($source)) {
    $context['results']['files'][$source] = $source;
    return copy($source, $dest);
  }
  // Make destination directory
  if (!is_dir($dest)) {
    mkdir($dest);
  }
  // Loop through the folder
  $dir = dir($source);
  while (false !== $entry = $dir->read()) {
    // skip over all the files types that make no sense in an export
    if ($entry == '.' || $entry == '..' || $entry == '.DS_Store' || $entry == '.htaccess' || $entry == '.project' || $entry == 'Thumbs.db' || $entry == '_notes' || strpos($entry, '.engine') != 0 || strpos($entry, '.php') != 0 || strpos($entry, '.inc') != 0 || strpos($entry, '.include') != 0 || strpos($entry, '.info') != 0 || strpos($entry, '.txt') != 0 || strpos($entry, '.ini') != 0 || strpos($entry, '.po') != 0 || strpos($entry, '.install') != 0 || strpos($entry, '.module') != 0 || strpos($entry, '.html') != 0 || strpos($entry, '.htm') != 0 || strpos($entry, '.test') != 0) {
      continue;
    }
    // Deep copy directories, ignore the html_export ones
    if ($dest !== "$source/$entry" && strpos($source, 'html_export') == 0 ) {
      _html_export_deep_file_copy("$source/$entry", "$dest/$entry", $context);
    }
  }
  // Clean up
  $dir->close();
  return TRUE;
}

/**
 * Batch 'finished' callback
 */
function html_export_finished($success, $results, $operations) {
  if ($success) {
    // Here we do something meaningful with the results.
    $message = t('Export created %count html pages and %files files.', array('%count' => count($results['paths']), '%files' => count($results['files'])));
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    $message = t('An error occurred while processing %error_operation with arguments: @arguments', array('%error_operation' => $error_operation[0], '@arguments' => print_r($error_operation[1], TRUE)));
  }
  drupal_set_message($message);
}

/**
 * Helper to tar a folder based on Features.
 */
function _html_export_download_folder($export_path, $token) {
  // verify token
  if (drupal_valid_token($token)) {
    $dir = file_create_path(file_directory_path() .'/html_export');
    file_check_directory($dir, 1);
    $filename = $export_path .'.tar';
    // Clear out output buffer to remove any garbage from tar output.
    if (ob_get_level()) {
      ob_end_clean();
    }
    drupal_set_header('Content-type: application/x-tar');
    drupal_set_header('Content-Disposition: attachment; filename="'. $filename .'"');
    $tar = array();
    // file_scan_directory() can throw warnings when using PHP 5.3, messing
    // up the output of our file stream. Suppress errors in this one case in
    // order to produce valid output.
    foreach (@file_scan_directory($dir .'/'. $export_path, '.*') as $file) {
      $filename = drupal_substr($file->filename, strpos($file->filename, $export_path));
      // Add this file.
      $contents = file_get_contents($file->filename);
      print html_export_tar_create("{$filename}", $contents);
      unset($contents);
    }
    print pack("a1024", "");
    exit;
  }
  else {
    drupal_set_message('Invalid token passed to html export download!', 'error');
  }
}

/**
 * Callback to push a directory to sftp via curl
 */
 /*
function html_export_sftp_folder($export_path, $token) {
  // verify token
  if (drupal_valid_token($token)) {
    $username = variable_get('html_export_sftp_name', '');
    $pass = variable_get('html_export_sftp_pass', '');
    $location = variable_get('html_export_sftp_location', '');
    // establish directory path
    $dir = file_create_path(file_directory_path() .'/html_export');
    file_check_directory($dir, 1);
    // deep copy everything in the export folder
    foreach (@file_scan_directory($dir .'/'. $export_path, '.*') as $file) {
      // set curl object
      $ch = curl_init();
      // set sftp info
      $connection = "sftp://$username:$pass@$location". str_replace($dir, '', $file->filename);
      curl_setopt($ch, CURLOPT_URL, $connection);
      // we will be uploading
      curl_setopt($ch, CURLOPT_UPLOAD, 1);
      // use SFTP protocol
      curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
      // open the current file
      $fp = fopen($file->filename, 'r');
      // set file for the operation
      curl_setopt($ch, CURLOPT_INFILE, $fp);
      // say how large the file is
      curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file->filename));
      // execute curl request
      curl_exec ($ch);
      // capture error messages potentially
      $error_code = curl_errno($ch);
      curl_close ($ch);
      // see if this caused an error
      if ($error_code != 0) {
        $error = 'Upload error!';
      }
    }
    return $error;
  }
  else {
    drupal_set_message('Invalid token passed to html export sftp!', 'error');
  }
}
*/ 