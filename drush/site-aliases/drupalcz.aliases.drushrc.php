<?php

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}

// Workaround: Acquia supports only drush 8 atm.
$drush_major_version = 8;

// Site drupalcz, environment dev
$aliases['dev'] = array(
  'root' => '/var/www/html/drupalcz.dev/docroot',
  'ac-site' => 'drupalcz',
  'ac-env' => 'dev',
  'ac-realm' => 'prod',
  'uri' => 'drupalczdev.prod.acquia-sites.com',
  'remote-host' => 'staging-6645.prod.hosting.acquia.com',
  'remote-user' => 'drupalcz.dev',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['dev.livedev'] = array(
  'parent' => '@drupalcz.dev',
  'root' => '/mnt/gfs/drupalcz.dev/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site drupalcz, environment prod
$aliases['prod'] = array(
  'root' => '/var/www/html/drupalcz.prod/docroot',
  'ac-site' => 'drupalcz',
  'ac-env' => 'prod',
  'ac-realm' => 'prod',
  'uri' => 'drupalcz.prod.acquia-sites.com',
  'remote-host' => 'ded-2299.prod.hosting.acquia.com',
  'remote-user' => 'drupalcz.prod',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['prod.livedev'] = array(
  'parent' => '@drupalcz.prod',
  'root' => '/mnt/gfs/drupalcz.prod/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site drupalcz, environment test
$aliases['test'] = array(
  'root' => '/var/www/html/drupalcz.test/docroot',
  'ac-site' => 'drupalcz',
  'ac-env' => 'test',
  'ac-realm' => 'prod',
  'uri' => 'drupalczstg.prod.acquia-sites.com',
  'remote-host' => 'staging-6517.prod.hosting.acquia.com',
  'remote-user' => 'drupalcz.test',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['test.livedev'] = array(
  'parent' => '@drupalcz.test',
  'root' => '/mnt/gfs/drupalcz.test/livedev/docroot',
);
