<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentExclude.
 */

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\component\Utility\Unicode;
use Drupal\features\FeaturesAssignmentMethodBase;
use Drupal\features\FeaturesManagerInterface;

/**
 * Class for excluding configuration from packages.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentExclude::METHOD_ID,
 *   weight = -5,
 *   name = @Translation("Exclude"),
 *   description = @Translation("Exclude configuration items from packaging by various methods including by configuration type."),
 *   config_route_name = "features.assignment_exclude"
 * )
 */
class FeaturesAssignmentExclude extends FeaturesAssignmentMethodBase {

  /**
   * The package assignment method id.
   */
  const METHOD_ID = 'exclude';

  /**
   * {@inheritdoc}
   */
  public function assignPackages() {
    $current_bundle = $this->assigner->getBundle();
    $settings = $current_bundle->getAssignmentSettings(self::METHOD_ID);

    $config_collection = $this->featuresManager->getConfigCollection();

    // Exclude by configuration type.
    $exclude_types = $settings['types'];
    if (!empty($exclude_types)) {
      foreach ($config_collection as $item_name => $item) {
        if (in_array($item['type'], $exclude_types)) {
          unset($config_collection[$item_name]);
        }
      }
    }

    // Exclude configuration already provided by modules.
    $exclude_module = $settings['module'];
    if (!empty($exclude_module['enabled'])) {
      $install_list = $this->featuresManager->getExtensionStorage()->listAll();
      // There are two settings that can limit what's included.
      // First, we can skipped configuration provided by the install profile.
      $module_profile = !empty($exclude_module['profile']);
      // Second, we can skip configuration provided by namespaced modules.
      $module_namespace = !empty($exclude_module['namespace']);
      if ($module_profile || $module_namespace) {
        $modules = array();
        // Load the names of any configuration objects provided by the install
        // profile.
        if ($module_profile) {
          $modules = array_merge($modules, $this->featuresManager->getModuleList([$current_bundle->getProfileName()]));
        }
        // Load the names of any configuration objects provided by modules
        // having the namespace of the current package set.
        if ($module_namespace) {
          $modules = array_merge($modules, $this->featuresManager->getAllModules($current_bundle));
        }
        // If any configuration was found, remove it from the list.
        foreach ($modules as $extension) {
          $extension_list = $this->featuresManager->listExtensionConfig($extension);
          $install_list = array_diff($install_list, $extension_list);
        }
      }
      foreach ($install_list as $item_name) {
        if (isset($config_collection[$item_name])) {
          unset($config_collection[$item_name]);
        }
      }
    }

    // Exclude configuration items on a curated list of site-specific
    // configuration.
    if ($settings['curated']) {
      $item_names = [
        'core.extension',
        'field.settings',
        'field_ui.settings',
        'filter.settings',
        'forum.settings',
        'image.settings',
        'node.settings',
        'system.authorize',
        'system.cron',
        'system.date',
        'system.file',
        'system.diff',
        'system.logging',
        'system.maintenance',
        'system.performance',
        'system.site',
        'system.theme',
        'system.theme.global',
        'update.settings',
      ];
      foreach ($item_names as $item_name) {
        unset($config_collection[$item_name]);
      }
    }

    // Register the updated data.
    $this->featuresManager->setConfigCollection($config_collection);
  }

}
