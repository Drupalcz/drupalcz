<?php

/**
 * @file
 * Contains the \Drupal\slack_invite\SlackInvite class.
 */

namespace Drupal\slack_invite;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class SlackInvite
 * @package Drupal\slack_invite
 */
class SlackInvite {

  use StringTranslationTrait;

  var $config = [];
  var $email = NULL;

  /**
   * @param            $email
   * @param bool|FALSE $direct
   */
  public function send($email, $direct = FALSE) {
    $this->email = $email;
    $this->config = \Drupal::config('slack_invite.settings');
    $method = !$this->config->get('twostep')['enabled'] ? 'sendDirect' : 'sendTwoStep';
    if ($direct == TRUE) {
      $method = 'sendDirect';
    }
    call_user_func([$this, $method]);
  }

  /**
   * Send the invite directly to the user.
   */
  protected function sendDirect() {
    $team_hostname = $this->config->get('hostname');
    $api_url = "https://{$team_hostname}.slack.com/api/users.admin.invite?t=" . time();

    $data = [
      'form_params' => [
        '_attempts'  => 1,
        'email'      => $this->email,
        'set_active' => 'true',
        'token'      => $this->config->get('token'),
      ],
    ];

    try {
      $client = \Drupal::httpClient();
      $response = $client->request('POST', $api_url, $data);

      $response_data = json_decode('' . $response->getBody());
      if ($response_data->ok == TRUE) {
        \Drupal::messenger()->addStatus(t('You will receive an email notification inviting you to join the slack team shortly.'));
      }
      else {
        $message = '';
        switch ($response_data->error) {
          case SLACK_INVITE_ALREADY_IN_TEAM:
            $message = $this->t('The user is already a member of the team');
            break;

          case SLACK_INVITE_SENT_RECENTLY:
            $message = $this->t('The user was recently sent an invitation.');
            break;

          case SLACK_INVITE_ALREADY_INVITED:
            $message = $this->t('The user is already invited.');
            break;

          default:
            $message = $data['error'];
            break;
        }
        \Drupal::messenger()->addStatus($this->t('There was an error sending your invite. Please contact the administrator with the following error details. The error message from slack was: @message', ['@message' => $message]));
      }
    }
    catch (Exception $e) {
      \Drupal::messenger()->addError($this->t('Something went wrong with the request. Please contact site administrator.'));
    }
  }

  /**
   * Send a two-step request for invitation to the designated Slack channel.
   */
  protected function sendTwoStep() {
    $token = $this->getEmailToken($this->email);
    $url = Url::fromRoute('slack_invite.twostep', [
      'email' => $this->email,
      'token' => $token,
    ], ['absolute' => TRUE]);
    $url = $url->toString();

    $team_hostname = $this->config->get('hostname');
    $api_url = "https://{$team_hostname}.slack.com/api/chat.postMessage?t=" . time();

    $message = $this->t($this->config->get('twostep')['message'], [
      '!email' => $this->email,
      '!url'   => $url,
    ]);

    $data = [
      'form_params' => [
        '_attempts' => 1,
        'token'     => $this->config->get('token'),
        'channel'   => $this->config->get('twostep')['channel'],
        'text'      => $message->render(),
      ],
    ];

    try {
      $client = \Drupal::httpClient();
      $response = $client->request('POST', $api_url, $data);

      $response_data = json_decode('' . $response->getBody());
      if ($response_data->ok == TRUE) {
        \Drupal::messenger()->addStatus($this->t('Your slack invitation request has been made and you will receive an email notification inviting you to join the slack team pending approval.'));
      }
      else {
        $message = '';
        switch ($response_data->error) {
          default:
            $message = $data['error'];
            break;
        }
        \Drupal::messenger()->addStatus($this->t('There was an error sending your invitation request. Please contact the administrator with the following error details. The error message from slack was: @message', ['@message' => $message]));
      }
    }
    catch (Exception $e) {
      \Drupal::messenger()->addError($this->t('Something went wrong with the request. Please contact site administrator.'));
    }
  }

  /**
   * @param $email
   * @return string
   */
  public function getEmailToken($email) {
    // Return the first 8 characters.
    return substr(Crypt::hmacBase64($email, $this->getPrivateKey() . $this->getHashSalt()), 0, 8);
  }

  /**
   * Gets the Drupal private key.
   *
   * @return string
   *   The Drupal private key.
   */
  protected function getPrivateKey() {
    return \Drupal::service('private_key')->get();
  }

  /**
   * Gets a salt useful for hardening against SQL injection.
   *
   * @return string
   *   A salt based on information in settings.php, not in the database.
   *
   * @throws \RuntimeException
   */
  protected function getHashSalt() {
    return Settings::getHashSalt();
  }
}
