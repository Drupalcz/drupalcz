<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesAssignmentMethodInterface.
 */

namespace Drupal\features;

use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesAssignerInterface;

/**
 * Interface for package assignment classes.
 */
interface FeaturesAssignmentMethodInterface {

  /**
   * Injects the features manager.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   The features manager to be used to retrieve the configuration list and
   *   the already assigned packages.
   */
  public function setFeaturesManager(FeaturesManagerInterface $features_manager);

  /**
   * Injects the features assigner.
   *
   * @param \Drupal\features\FeaturesAssignerInterface $assigner
   *   The features assigner to be used to retrieve the bundle configuration.
   */
  public function setAssigner(FeaturesAssignerInterface $assigner);

  /**
   * Performs package assignment.
   */
  public function assignPackages();

}
