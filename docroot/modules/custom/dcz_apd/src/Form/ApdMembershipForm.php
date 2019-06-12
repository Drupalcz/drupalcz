<?php

namespace Drupal\dcz_apd\Form;

use Drupal\Core\Url;
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

    // Profile selector.
    $profilesSource = $this->entityTypeManager->getStorage('profile')
      ->loadByProperties(['uid' => $this->currentUser()->id()]);
    $profiles = [];
    foreach ($profilesSource as $profile) {
      $profiles[$profile->id()] = "{$profile->get('field_fullname')->value} ({$profile->type->entity->label()})";
    }
    $profileIds = array_keys($profiles);
    $existingMembershipIds = $this->entityTypeManager->getStorage('apd_membership')
      ->getQuery()
      ->condition('profile_id', $profileIds, 'IN')
      ->condition('valid_to', 'NOW()', '<')
      ->execute();
    $existingMemberships = $this->entityTypeManager->getStorage('apd_membership')
      ->loadMultiple($existingMembershipIds);
    $existingMembershipProfiles = [];
    foreach ($existingMemberships as $existingMembership) {
      $existingMembershipProfiles[$existingMembership->getProfileId()] = $existingMembership->id();
    }

    $availableProfiles = array_diff_key($profiles, $existingMembershipProfiles);

    $form['profiles'] = [
      '#type' => 'select',
      '#options' => $availableProfiles,
      '#title' => $this->t('Vyberte profil, pro který se chcete stát členem'),
      '#description' => $this->t('Svoje profily můžete spravovat vo <a href=@url>svém používatelském účtu.</a>.', [
        '@url' => Url::fromRoute('user.page', [
          'user' => $this->currentUser()
            ->id(),
        ]),
      ]),
      '#required' => TRUE,
    ];

    $form['agreement_bylaws'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Souhlasím se stanovami Asociace pro Drupal.'),
      '#required' => TRUE,
    ];

    $form['agreement_gdpr'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Souhlasím se spracováním mých osobních údajů po dobu mého členství v Asociaci pro Drupal.'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Stát se členem'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\dcz_apd\Entity\ApdMembership $entity */
    $entity = $this->entity;
    $formValues = $form_state->getValues();
    $entity->setOwnerId($this->currentUser()->id());
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
