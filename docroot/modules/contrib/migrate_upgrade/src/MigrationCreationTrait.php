<?php

/**
 * @file
 * Contains \Drupal\migrate_upgrade\MigrationCreationTrait.
 */

namespace Drupal\migrate_upgrade;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Trait providing functionality to create the appropriate migrations for
 * a given source Drupal database. Note the class using the trait must
 * implement TranslationInterface (i.e., define t()).
 */
trait MigrationCreationTrait {

  /**
   * Set up the relevant migrations for import from the provided database
   * connection.
   *
   * @param \Drupal\Core\Database\Database $database
   *   Database array representing the source Drupal database.
   * @param string $source_base_path
   *   Address of the source Drupal site (e.g., http://example.com/).
   *
   * @return array
   */
  protected function createMigrations(array $database, $source_base_path) {
    // Set up the connection.
    Database::addConnectionInfo('upgrade', 'default', $database);
    $connection = Database::getConnection('default', 'upgrade');

    if (!$drupal_version = $this->getLegacyDrupalVersion($connection)) {
      throw new \Exception($this->t('Source database does not contain a recognizable Drupal version.'));
    }

    $version_tag = 'Drupal ' . $drupal_version;

    $template_storage = \Drupal::service('migrate.template_storage');
    $migration_templates = $template_storage->findTemplatesByTag($version_tag);
    foreach ($migration_templates as $id => $template) {
      // Configure file migrations so they can find the files.
      if ($template['destination']['plugin'] == 'entity:file') {
        if ($source_base_path) {
          // Make sure we have a single trailing slash.
          $source_base_path = rtrim($source_base_path, '/') . '/';
          $migration_templates[$id]['destination']['source_base_path'] = $source_base_path;
        }
      }
      // @todo: Use a group to hold the db info, so we don't have to stuff it
      // into every migration.
      $migration_templates[$id]['source']['key'] = 'upgrade';
      $migration_templates[$id]['source']['database'] = $database;
    }

    // Let the builder service create our migration configuration entities from
    // the templates, expanding them to multiple entities where necessary.
    /** @var \Drupal\migrate\MigrationBuilder $builder */
    $builder = \Drupal::service('migrate.migration_builder');
    $migrations = $builder->createMigrations($migration_templates);
    $migration_ids = [];
    foreach ($migrations as $migration) {
      try {
        if ($migration->getSourcePlugin() instanceof RequirementsInterface) {
          $migration->getSourcePlugin()->checkRequirements();
        }
        if ($migration->getDestinationPlugin() instanceof RequirementsInterface) {
          $migration->getDestinationPlugin()->checkRequirements();
        }
        // Don't try to resave migrations that already exist.
        if (!Migration::load($migration->id())) {
          $migration->save();
        }
        $migration_ids[] = $migration->id();
      }
      // Migrations which are not applicable given the source and destination
      // site configurations (e.g., what modules are enabled) will be silently
      // ignored.
      catch (RequirementsException $e) {
      }
      catch (PluginNotFoundException $e) {
      }
    }

    // loadMultiple will sort the migrations in dependency order.
    return array_keys(Migration::loadMultiple($migration_ids));
  }

  /**
   * Determine what version of Drupal the source database contains.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *
   * @return int|FALSE
   */
  protected function getLegacyDrupalVersion(Connection $connection) {
    // Don't assume because a table of that name exists, that it has the columns
    // we're querying. Catch exceptions and report that the source database is
    // not Drupal.

    // Druppal 5/6/7 can be detected by the schema_version in the system table.
    if ($connection->schema()->tableExists('system')) {
      try {
        $version_string = $connection->query('SELECT schema_version FROM {system} WHERE name = :module', [':module' => 'system'])
                                     ->fetchField();
        if ($version_string && $version_string[0] == '1') {
          if ((int) $version_string >= 1000) {
            $version_string = '5';
          }
          else {
            $version_string = FALSE;
          }
        }
      }
      catch (\PDOException $e) {
        $version_string = FALSE;
      }
    }
    // For Drupal 8 (and we're predicting beyond) the schema version is in the
    // key_value store.
    elseif ($connection->schema()->tableExists('key_value')) {
      $result = $connection->query("SELECT value FROM {key_value} WHERE collection = :system_schema  and name = :module", [':system_schema' => 'system.schema', ':module' => 'system'])->fetchField();
      $version_string = unserialize($result);
    }
    else {
      $version_string = FALSE;
    }

    return $version_string ? substr($version_string, 0, 1) : FALSE;
  }

}
