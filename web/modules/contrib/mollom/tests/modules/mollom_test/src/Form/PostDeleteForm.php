<?php

namespace Drupal\mollom_test\Form;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to delete a test post.
 */
class PostDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritDoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %title', array(
      '%title' => $this->getEntity()->label(),
    ));
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelURL() {
    return new Url('entity.mollom_test_post.edit_form', array('mollom_test_post' => $this->getEntity()->id()));
  }

  /**
   * {@inheritDoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getEntity()->delete();
    drupal_set_message('The record has been deleted.');
    $form_state->setRedirect('mollom_test.post_add_form');
  }
}
