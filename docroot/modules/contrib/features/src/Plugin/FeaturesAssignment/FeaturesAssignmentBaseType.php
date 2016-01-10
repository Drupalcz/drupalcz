<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentBaseType.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\component\Utility\Unicode;
use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to packages based on entity types.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentBaseType::METHOD_ID,
 *   weight = -2,
 *   name = @Translation("Base type"),
 *   description = @Translation("Use designated types of configuration as the base for configuration package modules. For example, if content types are selected as a base type, a package will be generated for each content type and will include all configuration dependent on that content type."),
 *   config_route_name = "features.assignment_base"
 * )
 */
class FeaturesAssignmentBaseType extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'base';

  /**
   * {@inheritdoc}
   */
  public function assignPackages() {
    $current_bundle = $this->assigner->getBundle();
    $settings = $current_bundle->getAssignmentSettings(self::METHOD_ID);
    $base_types = $settings['types'];

    $config_types = $this->featuresManager->listConfigTypes();
    $config_collection = $this->featuresManager->getConfigCollection();

    foreach ($config_collection as $item_name => $item) {
      if (in_array($item['type'], $base_types)) {
        if (!isset($packages[$item['name_short']]) && !isset($item['package'])) {
          $description = $this->t('Provide @label @type and related configuration.', array('@label' => $item['label'], '@type' => Unicode::strtolower($config_types[$item['type']])));
          if (isset($item['data']['description'])) {
            $description .= ' ' . $item['data']['description'];
          }
          $this->featuresManager->initPackage($item['name_short'], $item['label'], $description);
          try {
            $this->featuresManager->assignConfigPackage($item['name_short'], [$item_name]);
          }
          catch (\Exception $exception) {
            \Drupal::logger('features')->error($exception->getMessage());
          }
          $this->featuresManager->assignConfigDependents([$item_name]);
        }
      }
    }
  }

}
