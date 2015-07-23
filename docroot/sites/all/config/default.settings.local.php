<?php
$databases = array(
  'default' =>
    array(
      'default' =>
        array(
          'database' => 'TYPESOMETHING',
          'username' => 'TYPESOMETHING',
          'password' => 'TYPESOMETHING',
          'host' => 'localhost',
          'port' => '',
          'driver' => 'mysql',
          'prefix' => '',
        ),
    ),
);

// Stage file proxy
$conf['stage_file_proxy_origin'] = "http://USERNAME:" . urlencode('PASSWORD') . "@TYPESOMETHING";
$conf['stage_file_proxy_use_imagecache_root'] = 1;

// Acquia
$conf['stage_file_proxy_origin_dir'] = 'files';
$conf['stage_file_proxy_hotlink'] = 0;
$conf['stage_file_proxy_sslversion'] = 3;

// Files
// Paths MUST exist!
$conf['file_public_path'] = 'sites/default/files';
$conf['file_private_path'] = 'sites/default/files/private';
$conf['file_temporary_path'] = 'sites/default/files/tmp';

// Performance.
$conf['cache'] = FALSE;
$conf['block_cache'] = FALSE;
$conf['page_compression'] = FALSE;
$conf['cache_lifetime'] = 0;
$conf['page_cache_maximum_age'] = 0;
$conf['page_cache_invoke_hooks'] = TRUE;
$conf['preprocess_css'] = FALSE;
$conf['preprocess_js'] = FALSE;

// Environment
$conf['environment_indicator_color'] = 'black';
$conf['environment_indicator_text'] = 'LOCAL ENVIRONMENT';

// Disable shield
$conf['shield_user'] = '';
$conf['shield_pass'] = '';
$conf['shield_print'] = '';

// Set shield to allow command line access.
$conf['shield_allow_cli'] = 1;

// Email
$conf['mail_system'] = array(
  'default-system' => 'DevelMailLog',
);
