<?php

namespace Drupal\mollom\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormEditForm.
 *
 * Provides the add form for our Mollom Form entity.
 *
 * @package Drupal\mollom\Form
 *
 * @ingroup mollom
 */
class FormEdit extends FormFormBase {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the robot add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need form the base class.
    $form = parent::buildForm($form, $form_state);
    $form['label']['#disabled'] = TRUE;
    return $form;
  }

  /**
   * Returns the actions provided by this form.
   *
   * For our add form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {

    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update Protected Mollom Form');
    return $actions;
  }

}
