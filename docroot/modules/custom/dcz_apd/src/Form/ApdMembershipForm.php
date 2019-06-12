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
    $profileIds = array_keys($profiles);
    $existingMembershipIds = \Drupal::entityQuery('apd_membership')
      ->condition('profile_id', $profileIds, 'IN')
      ->condition('valid_to', 'NOW()', '<')
      ->execute();
    $existingMemberships = \Drupal::entityTypeManager()->getStorage('apd_membership')->loadMultiple($existingMembershipIds);
    $existingMembershipProfiles = [];
    foreach ($existingMemberships as $existingMembership) {
      $existingMembershipProfiles[$existingMembership->getProfileId()] = $existingMembership->id();
    }

    $availableProfiles = array_diff_key($profiles, $existingMembershipProfiles);

    $form['profiles'] = [
      '#type' => 'select',
      '#options' => $availableProfiles,
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

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\dcz_apd\Entity\ApdMembership $entity */
    $entity = $this->entity;
    $formValues = $form_state->getValues();
    $entity->setOwnerId(\Drupal::currentUser()->id());
    $entity->setValid(FALSE);
    $entity->setProfileId($formValues['profiles']);
    $entity->set('valid_from', time());
    $entity->set('valid_to', strtotime('+1year'));
    $entity->save();
    $this->messenger()
      ->addStatus('Děkujeme za projevený zájem stát se členem. Prosíme Vás o úhradu členského poplatku dle instrukcí níže.');
    $form_state->setRedirect('entity.apd_membership.canonical', ['apd_membership' => $entity->id()]);
  }

}
