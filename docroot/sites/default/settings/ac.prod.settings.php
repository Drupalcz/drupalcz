<?php

// Set base URL.
$base_url = 'https://www.drupal.cz';

$config['config_split.config_split.prod']['status'] = TRUE;
$config['config_split.config_split.default_content']['status'] = FALSE;
// @see https://www.drupal.org/project/dropzonejs/issues/2916330
$settings['file_temp_path'] = "/mnt/gfs/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}/tmp";
