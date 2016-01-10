<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesBundle.
 */

namespace Drupal\features;

use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesBundleInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines the Features Bundle object.
 */
class FeaturesBundle implements FeaturesBundleInterface {

  /**
   * The features manager.
   *
   * @var array
   */
  protected $featuresManager;

  /**
   * The package assigner.
   *
   * @var array
   */
  protected $assigner;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The machine name of the bundle.
   *
   * @var string
   */
  protected $machineName;

  /**
   * The human readable name of the bundle.
   *
   * @var string
   */
  protected $name;

  /**
   * The description of the bundle.
   *
   * @var string
   */
  protected $description;

  /**
   * Settings for the bundle.
   *
   * @var array
   */
  protected $settings;

  /**
   * The profile name associated with this bundle.
   *
   * @var string
   */
  protected $profileName;

  /**
   * Whether this bundle is a profile.
   *
   * @var bool
   */
  protected $isProfile;

  /**
   * A list of assignments.
   *
   * Assignments are keyed by assignment ID.
   *  - enabled: whether method is enabled.
   *  - weight' weight (order it is applied).
   *  - settings' method-specific settings.
   *
   * @var array
   */
  protected $assignments;

  /**
   * Constructs a FeaturesBundle object.
   */
  public function __construct($machine_name, FeaturesManagerInterface $features_manager, FeaturesAssignerInterface $assigner, ConfigFactoryInterface $config_factory) {
    $this->machineName = $machine_name;
    $this->featuresManager = $features_manager;
    $this->assigner = $assigner;
    $this->configFactory = $config_factory;
    $this->assignments = $this->initAssignments();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      '',
      $container->get('features.manager'),
      $container->get('features_assigner'),
      $container->get('config.factory')
    );
  }

  /**
   * Initializes the $assignments array with defaults for a new bundle.
   */
  protected function initAssignments() {
    $assignments = array();
    $enabled = $this->configFactory->get('features.settings')->get('assignment.enabled');
    $weights = $this->configFactory->get('features.settings')->get('assignment.method_weights');
    $settings = $this->configFactory->get('features.assignment');
    foreach ($this->assigner->getAssignmentMethods() as $method_id => $method) {
      $assignments[$method_id] = array(
        'enabled' => !empty($enabled[$method_id]),
        'weight' => !empty($weights[$method_id]) ? $weights[$method_id] : 0,
        'settings' => $settings->get($method_id),
      );
    }
    return $assignments;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return $this->machineName == '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName($machine_name) {
    $this->machineName = $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullName($short_name) {
    if ($this->isDefault() || $this->inBundle($short_name)) {
      return $short_name;
    }
    else {
      return $this->machineName . '_' . $short_name;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getShortName($machine_name) {
    if ($this->inBundle($machine_name)) {
      return substr($machine_name, strlen($this->getMachineName()) + 1, strlen($machine_name) - strlen($this->getMachineName()) - 1);
    }
    return $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function inBundle($machine_name) {
    return (strpos($machine_name, $this->machineName . '_') === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function isProfile() {
    return $this->isProfile;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsProfile($value) {
    $this->isProfile = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileName() {
    $name = $this->isProfile() ? $this->profileName : '';
    return !empty($name) ? $name : drupal_get_profile();
  }

  /**
   * {@inheritdoc}
   */
  public function setProfileName($machine_name) {
    $this->profileName = $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledAssignments() {
    $list = array();
    foreach ($this->assignments as $method_id => $method) {
      if ($method['enabled']) {
        $list[$method_id] = $method_id;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabledAssignments(array $assignments) {
    foreach ($this->assignments as $method_id => &$method) {
      $method['enabled'] = in_array($method_id, $assignments);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignmentWeights() {
    $list = array();
    foreach ($this->assignments as $method_id => $method) {
      $list[$method_id] = $method['weight'];
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssignmentWeights(array $assignments) {
    foreach ($this->assignments as $method_id => &$method) {
      if (isset($assignments[$method_id])) {
        $method['weight'] = $assignments[$method_id];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignmentSettings($method_id) {
    if (isset($method_id)) {
      if (isset($this->assignments[$method_id])) {
        return $this->assignments[$method_id]['settings'];
      }
    }
    else {
      $list = array();
      foreach ($this->assignments as $method_id => $method) {
        $list[$method_id] = $method['settings'];
      }
      return $list;
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function setAssignmentSettings($method_id, array $settings) {
    if (isset($method_id)) {
      if (isset($this->assignments[$method_id])) {
        $this->assignments[$method_id]['settings'] = $settings;
      }
    }
    else {
      foreach ($settings as $method_id => $method_settings) {
        if (!empty($method_settings)) {
          $this->setAssignmentSettings($method_id, $method_settings);
        }
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function load($machine_name = NULL) {
    $machine_name = isset($machine_name) ? $machine_name : $this->machineName;
    if ($machine_name == '') {
      // Return defaults set by module.
      $enabled = $this->configFactory->get('features.settings')->get('assignment.enabled');
      $weights = $this->configFactory->get('features.settings')->get('assignment.method_weights');
      $settings = $this->configFactory->get('features.settings')->get('bundle.settings');
      $assignment_settings = $this->configFactory->get('features.assignment');
      foreach ($this->assigner->getAssignmentMethods() as $method_id => $method) {
        $this->assignments[$method_id] = array(
          'enabled' => in_array($method_id, $enabled),
          'weight' => !empty($weights[$method_id]) ? $weights[$method_id] : 0,
          'settings' => $assignment_settings->get($method_id),
        );
      }
      $this->setMachineName('');
      $this->setProfileName('');
      $this->setIsProfile(FALSE);
      $this->setName(t('--None--'));
      $this->setDescription(t('Default bundle with no namespace.'));
      $this->setSettings($settings);
    }
    else {
      $bundle = $this->configFactory->get('features.bundles')->get($machine_name);
      $this->setMachineName($machine_name);
      $this->setName($bundle['name']);
      $this->setDescription($bundle['description']);
      $this->setEnabledAssignments($bundle['assignments']);
      $this->setAssignmentWeights($bundle['weights']);
      $this->setAssignmentSettings(NULL, $bundle['assignment_settings']);
      $this->setSettings($bundle['settings']);
      $this->setProfileName($bundle['profile_name']);
      $this->setIsProfile($bundle['is_profile']);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $bundle = array(
      'name' => $this->getName(),
      'description' => $this->getDescription(),
      'assignments' => array_keys($this->getEnabledAssignments()),
      'weights' => $this->getAssignmentWeights(),
      'assignment_settings' => $this->getAssignmentSettings(NULL),
      'settings' => $this->getSettings(),
      'profile_name' => $this->isProfile() ? $this->getProfileName() : '',
      'is_profile' => $this->isProfile(),
    );
    if ($this->getMachineName() == '') {
      // Save new default settings into config.
      $this->configFactory->getEditable('features.settings')->set('assignment.enabled', $bundle['assignments'])->save();
      $this->configFactory->getEditable('features.settings')->set('assignment.method_weights', $bundle['weights'])->save();
      $this->configFactory->getEditable('features.settings')->set('bundle.settings', $bundle['settings'])->save();
      $settings = $this->configFactory->getEditable('features.assignment');
      foreach ($bundle['assignment_settings'] as $method_id => $value) {
        $settings->set($method_id, $value);
      }
      $settings->save();
    }
    else {
      $this->configFactory->getEditable('features.bundles')->set($this->getMachineName(), $bundle)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function remove() {
    $this->configFactory->getEditable('features.bundles')->clear($this->getMachineName())->save();
  }

}
