<?php
/**
 * @file
 * Contains \Drupal\slack_invite\Form\SlackInviteTwoStepApproveForm.
 */

namespace Drupal\slack_invite\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Class SlackInviteTwoStepApproveForm
 * @package Drupal\slack_invite\Form
 */
class SlackInviteTwoStepApproveForm extends ConfirmFormBase {
  protected $email = NULL;
  protected $token = NULL;

  /**
   * SlackInviteTwoStepApproveForm constructor.
   */
  public function __construct() {
    $this->email = \Drupal::routeMatch()->getParameter('email');
    $this->token = \Drupal::routeMatch()->getParameter('token');
  }

  /**
   * Access check for form route.
   */
  public function access(AccountInterface $account) {
    $permission = $account->hasPermission('approve slack invite');

    $slack_invite = \Drupal::service('slack_invite');
    $token = $this->token == $slack_invite->getEmailToken($this->email);

    return AccessResult::allowedIf($permission && $token);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'slack_invite_twostep_approve_form';
  }

  /**
   * @inheritdoc
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * @inheritdoc
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to invite %email to the Slack team?', ['%email' => $this->email]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $slack_invite = \Drupal::service('slack_invite');
    $slack_invite->send(\Drupal::routeMatch()->getParameter('email'), TRUE);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
