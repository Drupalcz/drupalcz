<?php

namespace Drupal\mollom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\mollom\API\DrupalClient;
use Drupal\mollom\Utility\MollomUtilities;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures devel settings.
 */
class Settings extends ConfigFormBase {
  /**
   * Server communication failure fallback mode: Block all submissions of protected forms.
   */
  const MOLLOM_FALLBACK_BLOCK = 0;


  /**
   * Server communication failure fallback mode: Accept all submissions of protected forms.
   */
  const MOLLOM_FALLBACK_ACCEPT = 1;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mollom_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mollom.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    MollomUtilities::displayMollomTestModeWarning();

    $config = $this->config('mollom.settings');

    // Only check and display the status message if the form is being shown
    // for the first time and not when displayed again after submission.
    $check = empty($_POST);
    if ($check) {
      $status = MollomUtilities::getAdminAPIKeyStatus($check);
      if ($status['isVerified'] && !$config->get('test_mode.enabled')) {
        drupal_set_message(t('Mollom servers verified your keys. The services are operating correctly.'));
      }
    }

    $form['keys'] = array(
        '#type' => 'details',
        '#title' => t('Mollom API keys'),
        '#tree' => TRUE,
        '#description' => t('To obtain API keys, <a href="@signup-url">sign up</a> or log in to your <a href="@site-manager-url">Site manager</a>, register this site, and copy the keys into the fields below.', array(
            '@signup-url' => 'https://mollom.com/pricing',
            '@site-manager-url' => 'https://mollom.com/site-manager',
        )),
        // Only show key configuration fields if they are not configured or invalid.
        '#open' => isset($status) ? !$status['isVerified'] : true,
    );
    // Keys are not #required to allow to install this module and configure it
    // later.
    $form['keys']['public'] = array(
        '#type' => 'textfield',
        '#title' => t('Public key'),
        '#default_value' => $config->get('keys.public'),
        '#description' => t('Used to uniquely identify this site.'),
    );
    $form['keys']['private'] = array(
        '#type' => 'textfield',
        '#title' => t('Private key'),
        '#default_value' => $config->get('keys.private'),
        '#description' => t('Used for authentication. Similar to a password, the private key should not be shared with anyone.'),
    );

    $form['fallback'] = array(
        '#type' => 'radios',
        '#title' => t('When the Mollom service is unavailable'),
        '#default_value' => $config->get('fallback'),
        '#options' => array(
            Settings::MOLLOM_FALLBACK_ACCEPT => t('Accept all form submissions'),
            Settings::MOLLOM_FALLBACK_BLOCK => t('Block all form submissions'),
        ),
        '#description' => t('Mollom offers a <a href="@pricing-url">high-availability</a> infrastructure for users on paid plans to reduce potential downtime.', array(
            '@pricing-url' => 'https://mollom.com/pricing',
        )),
    );

    $options = DrupalClient::getSupportedLanguages();

    $default_languages = !empty($status['expectedLanguages']) ? $status['expectedLanguages'] : $config->get("languages_expected");
    // @todo: Add chosen UI functionality for improved UX when available.
    $form['languages_expected'] = array(
        '#type' => 'select',
        '#title' => t('Expected languages'),
        '#options' => $options,
        '#multiple' => TRUE,
        '#size' => 6,
        '#default_value' => $default_languages,
        '#description' => t('Restricts all posts to selected languages. Used by text analysis only. Leave empty if users may post in other languages.'),
    );

    $form['privacy_link'] = array(
        '#type' => 'checkbox',
        '#title' => t("Show a link to Mollom's privacy policy"),
        '#return_value' => true,
        '#default_value' => $config->get('privacy_link'),
        '#description' => t('Only applies to forms protected with text analysis. When disabling this option, you should inform visitors about the privacy of their data through other means.'),
    );

    $form['testing_mode'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable testing mode'),
        '#return_value' => true,
        '#default_value' => $config->get('test_mode.enabled'),
        '#description' => t('Submitting "ham", "unsure", or "spam" triggers the corresponding behavior; image CAPTCHAs only respond to "correct" and audio CAPTCHAs only respond to "demo". Do not enable this option if this site is publicly accessible.'),
    );

    $form['advanced'] = array(
        '#type' => 'details',
        '#title' => t('Advanced configuration'),
        '#open' => FALSE,
    );
    // Lower severity numbers indicate a high severity level.
    $form['advanced']['log_level'] = array(
        '#type' => 'radios',
        '#title' => t('Mollom logging level warning'),
        '#options' => array(
            RfcLogLevel::WARNING => t('Only log warnings and errors'),
            RfcLogLevel::DEBUG => t('Log all Mollom messages'),
        ),
        '#default_value' => $config->get('log_level'),
    );
    $form['advanced']['audio_captcha_enabled'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable audio CAPTCHAs.'),
        '#description' => t('Allows users to switch to an audio verification using the <a href=":faq-url">NATO alphabet</a>.  This may not be appropriate for non-English language sites.', array(
            ':faq-url' => 'https://mollom.com/faq/mollom-audible-captcha-language',
        )),
        '#return_value' => true,
        '#default_value' => $config->get('captcha.audio.enabled'),
    );
    $timeout = $config->get('connection_timeout_seconds');
    $form['advanced']['connection_timeout_seconds'] = array(
        '#type' => 'number',
        '#title' => t('Time-out when attempting to contact Mollom servers.'),
        '#description' => t('This is the length of time that a call to Mollom will wait before timing out.'),
        '#default_value' => !empty($timeout) ? $config->get('connection_timeout_seconds') : 3,
        '#size' => 5,
        '#field_suffix' => t('seconds'),
        '#required' => TRUE,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $message = $this->validateKey($form_state->getValue(array('keys','public')));
    if (!empty($message)) {
      $form_state->setError($form['keys']['public'], $message);
    }
    $message = $this->validateKey($form_state->getValue(array('keys','private')));
    if (!empty($message)) {
      $form_state->setError($form['keys']['private'], $message);
    }
    $languages = $form_state->getValue('languages_expected', []);
    $flattened_languages = DrupalClient::flattenExpectedLanguages($languages);
    if (strlen($flattened_languages) > DrupalClient::MAX_EXPECTED_LANGUAGES_LENGTH) {
      $form_state->setError($form['expected_languages'], $this->t('You have selected too many expected languages.  Mollom can only accept approximately 20 expected languages.'));
    }
  }

  /**
   * Validates a user-submitted Mollom key value.
   *
   * @param string $key
   *   The key value to validate.
   * @return string
   *   An error message to display or NULL if no errors found.
   */
  protected function validateKey($key) {
    $error = NULL;
    if (empty($key)) {
      return $error;
    }
    $key = trim($key);
    if (\Drupal\Component\Utility\Unicode::strlen($key) !== 32) {
      $error = $this->t('Keys must be 32 characters long.  Ensure you copied the key correctly.');
    }
    return $error;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('mollom.settings');
    $languages = !empty($values['languages_expected']) ? $values['languages_expected'] : [];

    $config->set('keys.public', $values['keys']['public'])
        ->set('keys.private', $values['keys']['private'])
        ->set('fallback', $values['fallback'])
        ->set('languages_expected', $languages)
        ->set('privacy_link', $values['privacy_link'])
        ->set('test_mode.enabled', $values['testing_mode'])
        ->set('log_level', $values['log_level'])
        ->set('captcha.audio.enabled', $values['audio_captcha_enabled'])
        ->set('connection_timeout_seconds', $values['connection_timeout_seconds'])
        ->save();

    parent::submitForm($form, $form_state);
    // Update Mollom site record with local configuration.
    MollomUtilities::getAPIKeyStatus(TRUE, TRUE);
  }

}
