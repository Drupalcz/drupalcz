<?php

// Environment
$conf['environment_indicator_color'] = 'blue';
$conf['environment_indicator_text'] = 'TEST ENVIRONMENT';

// Turn off mail
$conf['mail_system'] = array(
  'default-system' => 'DevelMailLog',
);

$conf['cache_backends'][] = './sites/all/modules/contrib/memcache/memcache.inc';
$conf['cache_default_class'] = 'MemCacheDrupal';
$conf['cache_class_cache_form'] = 'DrupalDatabaseCache';