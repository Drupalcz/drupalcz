<?php

namespace Drupal\dcz_apd\Form;

use Drupal;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for APD membership edit forms.
 *
 * @ingroup dcz_apd
 */
class ApdMembershipForm extends ContentEntityForm {

  use Drupal\Core\Messenger\MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $account = \Drupal::currentUser();

    // Profile selector
    $profilesSource = \Drupal::entityTypeManager()->getStorage('profile')
      ->loadByProperties(['uid' => $account->id()]);
    $profiles = [];
    foreach ($profilesSource as $profile) {
      $profiles[$profile->id()] = "{$profile->get('field_fullname')->value} ({$profile->type->entity->label()})";
    }
    $form['profiles'] = [
      '#type' => 'select',
      '#options' => $profiles,
      '#title' => 'Select profile',
      '#description' => 'You can edit profiles in <a href="/user/' . $account->id() . '/edit">your user account</a>.',
      '#required' => TRUE,
    ];

    $form['agreement'] = [
      '#type' => 'checkbox',
      '#title' => 'Souhlas se stanovami a zprac. osobních údajů',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Become a member',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId($this->currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addStatus($this->t('Created the %label APD membership.', [
            '%label' => $entity->label(),
          ]));
        break;

      default:
        $this->messenger()
          ->addStatus($this->t('Saved the %label APD membership.', [
            '%label' => $entity->label(),
          ]));
    }
    $form_state->setRedirect('entity.apd_membership.canonical', ['apd_membership' => $entity->id()]);
  }

}
