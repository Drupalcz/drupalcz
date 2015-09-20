<?php

/**
 * @file
 * Contains
 * \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentCoreType.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to a core package based on entity types.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentCoreType::METHOD_ID,
 *   weight = 0,
 *   name = @Translation("Core type"),
 *   description = @Translation("Assign designated types of configuration to a core configuration package module. For example, if image styles are selected as a core type, a core package will be generated and image styles will be assigned to it."),
 *   config_route_name = "features.assignment_core"
 * )
 */
class FeaturesAssignmentCoreType extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'core';

  /**
   * {@inheritdoc}
   */
  public function assignPackages() {
    $current_bundle = $this->assigner->getBundle();
    $settings = $current_bundle->getAssignmentSettings(self::METHOD_ID);
    $core_types = $settings['types'];

    $config_collection = $this->featuresManager->getConfigCollection();

    $initialized = FALSE;
    foreach ($config_collection as $item_name => $item) {
      if (in_array($item['type'], $core_types) && !isset($item['package'])) {
        if (!$initialized) {
          $this->featuresManager->initCorePackage();
          $initialized = TRUE;
        }
        try {
          $this->featuresManager->assignConfigPackage('core', [$item_name]);
        }
        catch (\Exception $exception) {
          \Drupal::logger('features')->error($exception->getMessage());
        }
      }
    }
  }

}
