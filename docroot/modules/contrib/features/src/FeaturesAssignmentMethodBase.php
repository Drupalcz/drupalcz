<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesAssignmentMethodBase.
 */

namespace Drupal\features;

use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for package assignment methods.
 */
abstract class FeaturesAssignmentMethodBase implements FeaturesAssignmentMethodInterface {
  use StringTranslationTrait;

  /**
   * The features manager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * The features assigner.
   *
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * {@inheritdoc}
   */
  public function setfeaturesManager(FeaturesManagerInterface $features_manager) {
    $this->featuresManager = $features_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssigner(FeaturesAssignerInterface $assigner) {
    $this->assigner = $assigner;
  }

}
