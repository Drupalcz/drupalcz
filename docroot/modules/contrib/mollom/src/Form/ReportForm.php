<?php

namespace Drupal\mollom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

abstract class MollomReportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mollom_report_form';
  }

  /**
   * Form builder for report to Mollom form.
   *
   * @param $entity
   *   The entity type of the data to report, e.g. 'node' or 'comment'.
   * @param $id
   *   The entity id the data belongs to.
   *
   * @see mollom_report_access()
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity = NULL, $id = NULL) {
    $form['entity'] = array(
      '#type' => 'value',
      '#value' => $entity,
    );
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $id,
    );
    // @todo "Delete" does not work for reporting mails to Mollom. In D7+, this
    //   form should be used solely for mails, as other entities are reported
    //   through existing delete confirmation forms instead. Perhaps there should
    //   be a dedicated form for reporting mails, as they are not really
    //   compatible with any of the standard processes either way.
    $form = confirm_form($form,
      t('Are you sure you want to delete and report the content as inappropriate?'),
      '<front>',
      t('This action cannot be undone.'),
      t('Delete'), t('Cancel')
    );
    mollom_data_delete_form_alter($form, $form_state);
    return $form;
  }

  /**
   * Form submit handler for mollom_report_form().
   */
  public function submit($form, &$form_state) {
    if ($form_state['values']['confirm']) {
      $entity = $form_state['values']['entity'];
      $id = $form_state['values']['id'];

      // Load the Mollom session data.
      $data = mollom_data_load($entity, $id);

      // Send feedback to Mollom, if we have session data.
      if ((!empty($data->contentId) || !empty($data->captchaId)) && !empty($form_state['values']['mollom']['feedback'])) {
        if (_mollom_send_feedback($data, $form_state['values']['mollom']['feedback'], 'moderate', 'mollom_report_form_submit')) {
          drupal_set_message(t('The content was successfully reported as inappropriate.'));
        }
      }

      // Delete the content. The callback should take care of proper deletion and
      // cache clearing on its own.
      foreach (mollom_form_list() as $form_id => $info) {
        if (!isset($info['entity']) || $info['entity'] != $entity) {
          continue;
        }
        // If there is a 'report delete callback', invoke it.
        if (isset($info['report delete callback']) && function_exists($info['report delete callback'])) {
          $function = $info['report delete callback'];
          $function($entity, $id);
          break;
        }
      }

      $form_state['redirect'] = '<front>';
    }
  }

}
