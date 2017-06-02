<?php
/**
 * @file
 * Contains \Drupal\dropguard\Form\SettingsForm.
 */

namespace Drupal\dropguard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropguard_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dropguard.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $dropguard_settings = $this->config('dropguard.settings');

    $form['info'] = array(
      '#type' => 'fieldset',
      '#title' => 'Drop Guard settings',
      '#description' => t('The information above is available when creating a 
        new project to support automated updates using <a href=":dropguard-link">Drop Guard</a>',
          array(':dropguard-link' => 'https://drop-guard.net')),
    );

    $form['info']['dropguard_client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('User ID'),
      '#description' => t('Copy the User ID here from the Drop Guard service. 
      It is available during the creation or editing process of a project.'),
      '#required' => TRUE,
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $dropguard_settings->get('dropguard.id'),
    );

    $form['info']['dropguard_openssl_public_key'] = array(
      '#type' => 'textarea',
      '#title' => t('Access Token'),
      '#description' => t('Copy the Access Token to this textarea from Drop Guard
       service. It is available during the creation or editing process of a project.'),
      '#required' => TRUE,
      '#size' => 6,
      '#default_value' => $dropguard_settings->get('dropguard.key'),
    );

    // Make sure that PHP OpenSSL library is enabled.
    // Print warning message and disable possibility to input data otherwise.
    if (!extension_loaded('openssl')) {
      $form['info']['#description'] .= '<br/><span style="color:red;">' .
        t('<a href=":url">PHP OpenSSL extension</a> is missing on your server.
            Drop Guard needs it for the secure transfer of information from your web site.',
          array(':url' => 'http://php.net/manual/en/book.openssl.php')) . '</span>';
      $form['info']['dropguard_client_id']['#disabled'] = TRUE;
      $form['info']['dropguard_openssl_public_key']['#disabled'] = TRUE;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Check if the client ID is numeric value.
    // TODO Add a check for positive integer.
    $client_id = $form_state->getValue('dropguard_client_id');
    $openssl_public_key = $form_state->getValue('dropguard_openssl_public_key');

    $form_state->setValue('dropguard_client_id', trim($client_id));
    $form_state->setValue('dropguard_openssl_public_key', trim($openssl_public_key));

    if (!is_numeric($form_state->getValue('dropguard_client_id'))) {
      $form_state->setErrorByName('dropguard_client_id', 'User ID value is invalid');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropguard.settings')
      ->set('dropguard.id', $form_state->getValue('dropguard_client_id'))
      ->set('dropguard.key', $form_state->getValue('dropguard_openssl_public_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
