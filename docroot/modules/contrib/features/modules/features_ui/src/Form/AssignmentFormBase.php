<?php

/**
 * @file
 * Contains \Drupal\features_ui\Form\AssignmentFormBase.
 */

namespace Drupal\features_ui\Form;

use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures the selected configuration assignment method for this site.
 */
abstract class AssignmentFormBase extends FormBase {

  /**
   * The features manager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * The package assigner.
   *
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * The current bundle.
   *
   * @var \Drupal\features\FeaturesBundleInterface
   */
  protected $currentBundle;

  /**
   * Constructs a AssignmentBaseForm object.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   The features manager.
   * @param \Drupal\features\FeaturesAssignerInterface $assigner
   *   The assigner.
   */
  public function __construct(FeaturesManagerInterface $features_manager, FeaturesAssignerInterface $assigner) {
    $this->featuresManager = $features_manager;
    $this->assigner = $assigner;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('features.manager'),
      $container->get('features_assigner')
    );
  }

  /**
   * Adds configuration types checkboxes.
   */
  protected function setTypeSelect(&$form, $defaults, $type) {
    $options = $this->featuresManager->listConfigTypes();

    $form['types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Types'),
      '#description' => $this->t('Select types of configuration that should be considered !type types.', array('!type' => $type)),
      '#options' => $options,
      '#default_value' => $defaults,
    );
  }

  /**
   * Adds a "Save settings" submit action.
   */
  protected function setActions(&$form) {
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save settings'),
    );
  }

  /**
   * Redirects back to the Bundle config form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function setRedirect(FormStateInterface $form_state) {
    $form_state->setRedirect('features.assignment', array('bundle_name' => $this->currentBundle->getMachineName()));
  }

}
