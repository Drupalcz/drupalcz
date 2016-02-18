<?php

namespace Drupal\mollom\API;

use Drupal\Core\Form\FormStateInterface;
use Drupal\mollom\Controller\FormController;
use Drupal\mollom\Entity\Form;
use Drupal\mollom\Storage\ResponseDataStorage;
use Drupal\mollom\Utility\Logger;

/**
 * FeedbackManager provides functionality related to reporting feedback
 * to the Mollom service.
 */
class FeedbackManager {

  /**
   * Add feedback options to an existing form, e.g., the delete form for
   * a protected entity.
   *
   * @see mollom_form_alter()
   */
  public static function addFeedbackOptions(&$form, FormStateInterface &$form_state) {
    if (!isset($form['description']['#weight'])) {
      $form['description']['#weight'] = 90;
    }
    $form['mollom'] = array(
      '#tree' => TRUE,
      '#weight' => 80,
    );
    $form['mollom']['feedback'] = array(
      '#type' => 'radios',
      '#title' => t('Report as…'),
      '#options' => array(
        'spam' => t('Spam, unsolicited advertising'),
        'profanity' => t('Profane, obscene, violent'),
        'quality' => t('Low-quality'),
        'unwanted' => t('Unwanted, taunting, off-topic'),
        '' => t('Do not report'),
      ),
      '#default_value' => 'spam',
      '#description' => t('Sending feedback to <a href="@mollom-url">Mollom</a> improves the automated moderation of new submissions.', array('@mollom-url' => 'https://mollom.com')),
    );
  }

  /**
   * Submit handler for feedback options.
   */
  public static function addFeedbackOptionsSubmit(&$form, FormStateInterface &$form_state) {
    $forms = FormController::getProtectedForms();
    $mollom_form = Form::load($forms['delete'][$form_state->getFormObject()->getFormId()])->initialize();
    $entity_type = $mollom_form['entity'];
    if (!empty($entity_type)) {
      $id = $form_state->getFormObject()->getEntity()->id();
    }
    else {
      $id = $form_state->getValue($mollom_form['mapping']['post_id']);
    }

    $feedback = $form_state->getValue(array('mollom', 'feedback'));
    if (!empty($feedback)) {
      if (self::sendFeedback($entity_type, $id, $feedback, 'moderate', 'mollom_data_delete_form_submit')) {
        drupal_set_message(t('The content was successfully reported as inappropriate.'));
      }
    }

    // Remove Mollom session data.
    ResponseDataStorage::delete($entity_type, $id);
  }

  /**
   * Sends feedback for a Mollom session data record.
   *
   * @param $entity
   *   The entity type to send feedback for.
   * @param $id
   *   The entity id to send feedback for.
   * @param $feedback
   *   The feedback reason for reporting content.
   * @param $type
   *   The type of feedback, one of 'moderate' or 'flag'.
   * @param $source
   *   An optional single word string identifier for the user interface source.
   *   This is tracked along with the feedback to provide a more complete picture
   *   of how feedback is used and submitted on the site.
   */
  public static function sendFeedback($entity, $id, $feedback, $type = 'moderate', $source = 'mollom_data_report') {
    return self::sendFeedbackMultiple($entity, array($id), $feedback, $type, $source);
  }

  /**
   * Sends feedback for multiple Mollom session data records.
   *
   * @param $entity
   *   The entity type to send feedback for.
   * @param $ids
   *   An array of entity ids to send feedback for.
   * @param $feedback
   *   The feedback reason for reporting content.
   * @param $type
   *   The type of feedback, one of 'moderate' or 'flag'.
   * @param $source
   *   An optional single word string identifier for the user interface source.
   *   This is tracked along with the feedback to provide a more complete picture
   *   of how feedback is used and submitted on the site.
   */
  public static function sendFeedbackMultiple($entity, array $ids, $feedback, $type = 'moderate', $source = 'mollom_data_report_multiple') {
    $return = TRUE;
    foreach ($ids as $id) {
      // Load the Mollom session data.
      $data = ResponseDataStorage::loadByEntity($entity, $id);
      if (empty($data)) {
        continue;
      }
      // Send feedback, if we have session data.
      if (!empty($data->contentId) || !empty($data->captchaId)) {
        $result = self::sendFeedbackToMollom($data, $feedback, $type, $source);
        $return = $return && $result;
      }
      $data->moderate = 0;
      ResponseDataStorage::save($data);
    }
    return $return;
  }

  /**
   * Send feedback to Mollom.
   *
   * @param $data
   *   A Mollom data record containing one or both of:
   *   - contentId: The content ID to send feedback for.
   *   - captchaId: The CAPTCHA ID to send feedback for.
   * @param $reason
   *   The feedback to send, one of 'spam', 'profanity', 'quality', 'unwanted',
   *   'approve'.
   * @param $type
   *   The type of feedback, one of 'moderate' or 'flag'.
   * @param $source
   *   An optional single word string identifier for the user interface source.
   *   This is tracked along with the feedback to provide a more complete picture
   *   of how feedback is used and submitted on the site.
   */
  protected static function sendFeedbackToMollom($data, $reason = 'spam', $type = 'moderate', $source = NULL) {
    $params = array();
    $current_user = \Drupal::currentUser();
    if (!empty($data->captchaId)) {
      $params['captchaId'] = $data->captchaId;
      $resource = 'CAPTCHA';
      $id = $data->captchaId;
    }
    // In case we also have a contentId, also pass that, and override $resource
    // and $id for the log message.
    if (!empty($data->contentId)) {
      $params['contentId'] = $data->contentId;
      $resource = 'content';
      $id = $data->contentId;
    }
    if (!isset($id)) {
      return FALSE;
    }
    $params += array(
      'reason' => $reason,
      'type' => $type,
      'authorIp' => \Drupal::request()->getClientIp(),
    );
    if (!empty($source)) {
      $params['source'] = $source;
    }
    if ($current_user->isAuthenticated()) {
      $params['authorId'] = $current_user->id();
    }

    $result = \Drupal::service('mollom.client')->sendFeedback($params);
    Logger::addMessage(array(
      'message' => 'Reported %feedback for @resource %id from %source - %type.',
      'arguments' => array(
        '%type' => $type,
        '%feedback' => $reason,
        '@resource' => $resource,
        '%id' => $id,
        '%source' => $source,
      ),
    ));
    return $result;
  }
}
