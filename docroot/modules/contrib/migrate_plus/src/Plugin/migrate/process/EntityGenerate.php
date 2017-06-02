<?php

/**
 * @file
 * Contains Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate.
 */

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin generates entity stubs.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_generate"
 * )
 *
 * @see EntityLookup
 *
 * All the configuration from the lookup plugin applies here. In it's most
 * simple form, this plugin needs no configuration. If there are fields on the
 * stub entity that are required or need some default value, that can be
 * provided via a default_values configuration option.
 *
 * Example usage with default_values configuration:
 * @code
 * destination:
 *   plugin: 'entity:node'
 * process:
 *   type:
 *     plugin: default_value
 *     default_value: page
 *   field_tags:
 *     plugin: entity_generate
 *     source: tags
 *     default_values:
 *       description: Stub description
 *       field_long_description: Stub long description
 * @endcode
 */
class EntityGenerate extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    // Creates a stub entity if one doesn't exist.
    if (!($result = parent::transform($value, $migrateExecutable, $row, $destinationProperty))) {
      $result = $this->generateEntity($value);
    }

    return $result;
  }

  /**
   * Generates stub entity for a given value.
   *
   * @param string $value
   *   Value to use in creation of stub entity.
   *
   * @return int|string
   *   The entity id of the generated entity.
   */
  protected function generateEntity($value) {
    if(!empty($value)) {
      $entity = $this->entityManager
        ->getStorage($this->lookupEntityType)
        ->create($this->stub($value));
      $entity->save();

      return $entity->id();
    }
  }

  /**
   * Fabricate a stub entity.
   *
   * This is intended to be extended by implementing classes to provide for more
   * dynamic default values, rather than just static ones.
   *
   * @param $value
   *   Value to use in creation of stub entity.
   *
   * @return array
   *   The stub entity.
   */
  protected function stub($value) {
    $stub = [$this->lookupValueKey => $value];

    if ($this->lookupBundleKey) {
      $stub[$this->lookupBundleKey] = $this->lookupBundle;
    }

    // Gather any static default values for properties/fields.
    if (isset($this->configuration['default_values']) && is_array($this->configuration['default_values'])) {
      foreach ($this->configuration['default_values'] as $key => $value) {
        $stub[$key] = $value;
      }
    }

    return $stub;
  }

}
