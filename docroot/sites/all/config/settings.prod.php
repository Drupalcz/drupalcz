<?php

// Set base URL.
$base_url = 'http://www.drupal.cz';

// Turn environment off
$conf['error_level'] = 0;
$conf['environment_indicator_enabled'] = FALSE;

// Turn shield off
$conf['shield_user'] = '';
$conf['shield_pass'] = '';

$conf['cache_backends'][] = './sites/all/modules/contrib/memcache/memcache.inc';
$conf['cache_default_class'] = 'MemCacheDrupal';
$conf['cache_class_cache_form'] = 'DrupalDatabaseCache';
