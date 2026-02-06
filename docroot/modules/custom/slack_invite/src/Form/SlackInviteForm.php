<?php
/**
 * @file
 * Contains \Drupal\slack_invite\Form\SlackInviteForm.
 */

namespace Drupal\slack_invite\Form;

use Drupal\Core\Http\Client;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Exception;
use Drupal\Core\Url;

/**
 * Builds the search form for the search block.
 */
class SlackInviteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'slack_invite_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('slack_invite.settings');

    $form['#action'] = Url::fromRoute('<current>', ['query' => $this->getDestinationArray(), 'external' => FALSE])->toString();
    $form['slack_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Enter email address for slack invite'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send')
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('slack_email');
    if (!\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('slack_email', $this->t('Enter email address in valid format (ex. example@example.com)'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $slack_invite = \Drupal::service('slack_invite');
    $slack_invite->send($form_state->getValue('slack_email'));
  }
}
