<?php

namespace Drupal\mollom\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormDeleteForm.
 *
 * Provides a confirm form for deleting the entity. This is different from the
 * add and edit forms as it does not inherit from RobotFormBase. The reason for
 * this is that we do not need to build the same form. Instead, we present the
 * user with a simple yes/no question. For this reason, we derive from
 * EntityConfirmFormBase instead.
 *
 * @package Drupal\mollom\Form
 *
 * @ingroup mollom
 */
class FormDelete extends EntityConfirmFormBase {

  /**
   * Gathers a confirmation question.
   *
   * The question is used as a title in our confirm form. For delete confirm
   * forms, this typically takes the form of "Are you sure you want to
   * delete...", including the entity label.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove protection from the %label form?', array(
      '%label' => $this->entity->label(),
    ));
  }

  /**
   * Gather the confirmation text.
   *
   * The confirm text is used as a the text in the button that confirms the
   * question posed by getQuestion().
   *
   * @return string
   *   Translated string.
   */
  public function getConfirmText() {
    return $this->t('Remove Mollom Protection');
  }

  /**
   * Gets the cancel URL.
   *
   * Provides the URL to go to if the user cancels the action. For entity
   * delete forms, this is typically the route that points at the list
   * controller.
   *
   * @return \Drupal\Core\Url
   *   The URL to go to if the user cancels the deletion.
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.mollom_form.list');
  }

  /**
   * The submit handler for the confirm form.
   *
   * For entity delete forms, you use this to delete the entity in
   * $this->entity.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    drupal_set_message(t('The form protection has been removed.'));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirect('entity.mollom_form.list');
  }

}
