<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesGenerationMethodBase.
 */

namespace Drupal\features;

use Drupal\Component\Serialization\Yaml;
use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesBundleInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for package assignment methods.
 */
abstract class FeaturesGenerationMethodBase implements FeaturesGenerationMethodInterface {
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
  public function setFeaturesManager(FeaturesManagerInterface $features_manager) {
    $this->featuresManager = $features_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssigner(FeaturesAssignerInterface $assigner) {
    $this->assigner = $assigner;
  }

  /**
   * {@inheritdoc}
   */
  public function exportFormSubmit(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Merges an info file into a package's info file.
   *
   * @param string $package_info
   *   The Yaml encoded package info.
   * @param string $info_file_uri
   *   The info file's URI.
   */
  protected function mergeInfoFile($package_info, $info_file_uri) {
    $package_info = Yaml::decode($package_info);
    $existing_info = \Drupal::service('info_parser')->parse($info_file_uri);
    // Ensure the entire 'features' data is replaced by new data.
    unset($existing_info['features']);
    return Yaml::encode($this->featuresManager->arrayMergeUnique($existing_info, $package_info));
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$packages = array(), FeaturesBundleInterface $bundle = NULL) {
    // If no packages were specified, get all packages.
    if (empty($packages)) {
      $packages = $this->featuresManager->getPackages();
    }

    // If any packages exist, read in their files.
    $existing_packages = $this->featuresManager->listPackageDirectories(array_keys($packages), $bundle);

    foreach ($packages as &$package) {
      list($full_name, $path) = $this->featuresManager->getExportInfo($package, $bundle);
      $package['directory'] = $path . '/' . $full_name;
      $this->preparePackage($package, $existing_packages, $bundle);
    }
    // Clean up the $package pass by reference.
    unset($package);

    if (isset($bundle) && $bundle->isProfile()) {
      $profile_name = $bundle->getProfileName();
      $profile_package = $this->featuresManager->getPackage($profile_name);
      if (isset($profile_package)) {
        $package['directory'] = 'profiles/' . $profile_name;
        $this->preparePackage($profile_package, $existing_packages, $bundle);
      }
    }
  }

  /**
   * Performs any required changes on a package prior to generation.
   *
   * @param array $package
   *   The package to be prepared.
   * @param array $existing_packages
   *   An array of existing packages with machine names as keys and paths as
   *   values.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   Optional bundle used for export
   */
  abstract protected function preparePackage(array &$package, array $existing_packages, FeaturesBundleInterface $bundle = NULL);

}
