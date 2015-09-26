<?php

/**
 * @file
 * Command-line tools to aid performing and developing upgrade migrations.
 */

use Drupal\migrate_upgrade\MigrateUpgradeDrushRunner;

/**
 * Implements hook_drush_command().
 */
function migrate_upgrade_drush_command() {
  $items['migrate-upgrade'] = [
    'description' => 'Perform one or more upgrade processes.',
    'options' => [
      'legacy-db-url' => 'A Drupal 6 style database URL. Required.',
      'legacy-db-prefix' => 'Prefix of the legacy Drupal installation.',
      'legacy-root' => 'Site address or root of the legacy Drupal installation',
      'configure-only' => 'Set up the appropriate upgrade processes but do not perform them',
    ],
    'examples' => [
      'migrate-upgrade --legacy-db-url=\'mysql://root:pass@127.0.0.1/d6\'' => 'Upgrade a Drupal 6 database to Drupal 8',
    ],
    'drupal dependencies' => ['migrate_upgrade'],
  ];

  return $items;
}

/**
 * Execute the upgrade command, configuring the necessary migrations and
 * optionally perform the imports.
 */
function drush_migrate_upgrade() {
  $configure_only = drush_get_option('configure-only');

  $runner = new MigrateUpgradeDrushRunner();

  $runner->configure();
  if (!$configure_only) {
    $runner->import();
  }
}
