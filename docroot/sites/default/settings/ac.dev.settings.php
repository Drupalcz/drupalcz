<?php

// Turn off mail.
$conf['mail_system'] = array(
  'default-system' => 'DevelMailLog',
);

$config['config_split.config_split.dev']['status'] = TRUE;
$config['config_split.config_split.default_content']['status'] = FALSE;
// @see https://www.drupal.org/project/dropzonejs/issues/2916330
$settings['file_temp_path'] = "/mnt/tmp/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}";
