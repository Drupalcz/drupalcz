<?php

namespace Drupal\mollom\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\mollom\Controller\FormController;

/**
 * Defines the form entity.
 *
 * @ingroup mollom
 *
 * @ConfigEntityType(
 *   id = "mollom_form",
 *   label = @Translation("Mollom Form Configuration"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\mollom\Controller\FormListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mollom\Form\FormAdd",
 *       "edit" = "Drupal\mollom\Form\FormEdit",
 *       "delete" = "Drupal\mollom\Form\FormDelete",
 *     },
 *   },
 *   admin_permission = "administer mollom",
 *   config_prefix = "form",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/mollom/form/{mollom_form}/edit",
 *     "delete-form" = "/admin/config/content/mollom/form/{mollom_form}/delete",
 *   }
 * )
 */
class Form extends ConfigEntityBase implements FormInterface {

  /**
   * The form ID.
   *
   * @var string
   */
  public $id;

  /**
   * The form UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The form label.
   *
   * @var string
   */
  public $label;

  /**
   * The form checks.
   *
   * @var array
   */
  public $checks = array();

  /**
   * The form mode.
   *
   * @var string
   */
  public $mode;

  /**
   * The form fields to analyze.
   *
   * @var array
   */
  public $enabled_fields = array();

  /**
   * The strictness of the analyzer.
   *
   * @var string
   */
  public $strictness = 'normal';

  /**
   * What to do if Mollom is not sure.
   *
   * @var string
   */
  public $unsure = 'captcha';

  /**
   * What to do if Mollom identified it as spam.
   *
   * @var boolean
   */
  public $discard = TRUE;

  /**
   * Stored mapping of the Drupal fields to Mollom fields.
   *
   * @var array
   */
  public $mapping = array();

  /**
   * The module that manages the protected form.
   *
   * @var string
   */
  public $module;

  /**
   * The entity of the protected form.
   *
   * @var string
   */
  public $entity;

  /**
   * The entity bundle of the protected form.
   *
   * @var string
   */
  public $bundle;

  /**
   * {@inheritDoc}
   *
   */
  public function initialize($form_id = NULL) {
    $mollom_form = get_object_vars($this);
    if (empty($form_id) && empty($this->id)) {
      return $mollom_form;
    }
    if ($this->isNew()) {
      $forms = FormController::getProtectableForms();
      if (empty($forms[$form_id])) {
        return $mollom_form;
      }
      $mollom_form += $forms[$form_id];
      $this->id = $form_id;
      $this->label = $forms[$form_id]['title'];
      foreach ($forms[$form_id] as $name => $value) {
        if (property_exists($this, $name)) {
          $this->{$name} = $value;
        }
      }
      $module = $this->module;
    }
    else {
      $form_id = $this->id();
      $module = $this->module;
      $forms = NULL;
    }
    // Add all of the configuration information defined in hooks.
    $form_details = FormController::getProtectedFormDetails($form_id, $module, $forms);
    if ($this->isNew()) {
      // Overwrite the element properties with form details when supplied.
      $mollom_form = array_merge($mollom_form, $form_details);
    }
    else {
      // The entity has already been configured so use it's data over the
      // configuration details.
      $mollom_form = array_merge($form_details, $mollom_form);
    }

    if ($this->isNew()) {
      // Enable all fields for textual analysis by default.
      $this->setChecks(array('spam'));
      $mollom_form['checks'] = array('spam');

      $mollom_form['enabled_fields'] = array_keys($mollom_form['elements']);
      $this->setEnabledFields(array_keys($mollom_form['elements']));

      // Set the defaults
      foreach ($mollom_form as $field => $value) {
        if (property_exists($this, $field) && !empty($value)) {
          $this->{$field} = $value;
        }
      }
    }

    return $mollom_form;
  }

  /**
   * {@inheritDoc}
   */
  public function getChecks() {
    return $this->checks;
  }

  /**
   * {@inheritDoc}
   */
  public function setChecks($checks) {
    $this->checks = $checks;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getProtectionMode() {
    return $this->mode;
  }

  /**
   * {@inheritDoc}
   */
  public function setProtectionMode($mode) {
    if (in_array($mode, array(FormInterface::MOLLOM_MODE_ANALYSIS, FormInterface::MOLLOM_MODE_CAPTCHA))) {
      $this->mode = $mode;
    }
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getEnabledFields() {
    return $this->enabled_fields;
  }

  /**
   * {@inheritDoc}
   */
  public function setEnabledFields(array $fields) {
    $this->enabled_fields = $fields;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getStrictness() {
    return $this->strictness;
  }

  /**
   * {@inheritDoc}
   */
  public function setStrictness($level) {
    // @todo: Convert strictness levels to constants.
    if (in_array($level, array('normal', 'unsure', 'relaxed'))) {
      $this->level = $level;
    }
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getUnsure() {
    return $this->unsure;
  }

  /**
   * {@inheritDoc}
   */
  public function setUnsure($handling) {
    // @todo: Convert unsure handling values to constants.
    if (in_array($handling, array('captcha', 'moderate', 'binary'))) {
      $this->unsure = $handling;
    }
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getDiscard() {
    return $this->discard;
  }

  /**
   * {@inheritDoc}
   */
  public function setDiscard($discard) {
    $this->discard = $discard;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getMapping() {
    return $this->mapping;
  }

  /**
   * {@inheritDoc}
   */
  public function setMapping(array $mapping) {
    $this->mapping = $mapping;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * {@inheritDoc}
   */
  public function setModule($module) {
    $this->module = $module;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritDoc}
   */
  public function setEntity($entity) {
    $this->entity = entity;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritDoc}
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }
}
