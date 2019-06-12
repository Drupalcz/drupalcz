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
    $availableProfiles = $profiles;
    $profileIds = array_keys($profiles);
    if (!empty($profileIds)) {
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
    }

    $text = $this->t('Pro účely přihlášky ke členství v asociaci potřebujeme více údajů. Přihlášku můžete spojit s jedním osobním a několika firemními profily. Můžete mít tedy členství jako soukromá osoba i jako firma. Svůj osobní profil či firemní profily si vytvořte ve <a href="@url">svém uživatelském účtu</a>.', [
      '@url' => Url::fromRoute('user.page', [
        'user' => $this->currentUser()
          ->id(),
      ])->toString(),
    ])->render();

    $form['profiles'] = [
      '#type' => 'select',
      '#options' => $availableProfiles,
      '#title' => $this->t('Vyberte profil pro členství v asociaci:'),
      '#description' => $text,
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
      ->addStatus('Děkujeme za Vaši přihlášku ke členství v asociaci. Prosíme Vás o úhradu členského poplatku dle instrukcí na této stránce.');
    $form_state->setRedirect('entity.apd_membership.canonical', ['apd_membership' => $entity->id()]);
  }

}
