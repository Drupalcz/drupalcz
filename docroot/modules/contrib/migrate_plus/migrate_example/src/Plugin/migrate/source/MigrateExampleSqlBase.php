<?php

/**
 * @file
 * Contains \Drupal\migrate_example\Plugin\migrate\source\MigrateExampleSqlBase.
 */

namespace Drupal\migrate_example\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base source class for beer tables.
 */
abstract class MigrateExampleSqlBase extends SqlBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
  }

}
