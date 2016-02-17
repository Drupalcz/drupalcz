<?php

// Make second DB accessable for travis.
$databases['migrate']['default'] = array (
  'database' => 'd6migratesource',
  'username' => 'root',
  'password' => '',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
