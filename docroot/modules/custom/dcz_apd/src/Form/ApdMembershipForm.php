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
      '#title' => 'Vyberte profil, pro který se chcete stát členem',
      '#description' => 'Svoje profily můžete spravovat vo <a href="/user/' . $account->id() . '/edit">svém používatelském účtu.</a>.',
      '#required' => TRUE,
    ];

    $form['agreement_bylaws'] = [
      '#type' => 'checkbox',
      '#title' => 'Souhlasím se stanovami Asociace pro Drupal.',
      '#required' => TRUE,
    ];

    $form['agreement_gdpr'] = [
      '#type' => 'checkbox',
      '#title' => 'Souhlasím se spracováním mých osobních údajů po dobu mého členství v Asociaci pro Drupal.',
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Stát se členem',
      '#button_type' => 'primary',
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
