<?php

/**
 * @file
 * Contains \Drupal\migrate_upgrade\MigrateUpgradeDrushRunner.
 */

namespace Drupal\migrate_upgrade;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate_tools\DrushLogMigrateMessage;

class MigrateUpgradeDrushRunner {

  use MigrationCreationTrait;
  use StringTranslationTrait;

  /**
   * The list of migrations to run and their configuration.
   *
   * @var array
   */
  protected $migrationList;

  /**
   * From the provided source information, instantiate the appropriate migrations
   * in the active configuration.
   *
   * @throws \Exception
   */
  public function configure() {
    $db_url = drush_get_option('legacy-db-url');
    $db_spec = drush_convert_db_from_db_url($db_url);
    $db_prefix = drush_get_option('legacy-db-prefix');
    $db_spec['prefix'] = $db_prefix;

    $this->migrationList = $this->createMigrations($db_spec, drush_get_option('legacy-root'));
  }

  /**
   * Run the configured migrations.
   */
  public function import() {
    $log = new DrushLogMigrateMessage();
    foreach ($this->migrationList as $migration_id) {
      /** @var MigrationInterface $migration */
      $migration = Migration::load($migration_id);
      drush_print(dt('Upgrading !migration', ['!migration' => $migration_id]));
      $executable = new MigrateExecutable($migration, $log);
      // drush_op() provides --simulate support.
      drush_op([$executable, 'import']);
    }
  }

}
