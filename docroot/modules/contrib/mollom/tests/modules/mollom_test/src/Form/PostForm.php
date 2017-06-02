<?php

namespace Drupal\mollom_test\Form;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for the mollom_test post entity.
 */
class PostForm extends ContentEntityForm {

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $state = \Drupal::state();

    $form = parent::buildForm($form, $form_state);
    // Due to #limit_validation_errors, submitting the form with the "Add" button
    // will only expose validated values in the submit handler, so our storage may
    // be incomplete. Therefore, the default values always have to be overloaded.
    $stored_record = $entity->getStorageRecord();
    $stored_record += array(
      'exclude' => '',
      'parent' => array('child' => ''),
      'field' => array(),
    );
    $storage = $form_state->get('mollom_test');
    $storage = empty($storage) ? array() : $storage;
    $stored_record = array_merge($stored_record, $storage);
    // Always add an empty field the user can submit.
    $stored_record['field']['new'] = '';
    $form_state->set('mollom_test', $stored_record);

    // Output a page view counter for page/form cache testing purposes.
    $count = $state->get('mollom_test.view_count', 0);

    $reset_link = \Drupal::l($this->t('Reset'), Url::fromRoute('mollom_test.views_reset', [], ['query' => $this->getDestinationArray()]));
    $form['views'] = array(
      '#markup' => '<p>' . 'Views: ' . $count++ . ' ' . $reset_link . '</p>',
    );
    $state->set('mollom_test.view_count', $count);

    $form['#tree'] = TRUE;
    $form['mid'] = array(
      '#type' => 'hidden',
      '#value' => $stored_record['mid'],
    );
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => 'Title',
      '#default_value' => $stored_record['title'],
      '#required' => TRUE,
    );
    $form['body'] = array(
      '#type' => 'textfield',
      '#title' => 'Body',
      '#default_value' => $stored_record['body'],
    );
    $form['exclude'] = array(
      '#type' => 'textfield',
      '#title' => 'Some other field',
      '#default_value' => $stored_record['exclude'],
    );
    $form['parent']['child'] = array(
      '#type' => 'textfield',
      '#title' => 'Nested element',
      '#default_value' => $stored_record['parent']['child'],
    );

    $form['field'] = array(
      '#type' => 'fieldset',
      '#title' => 'Field',
    );
    $weight = 0;
    foreach ($stored_record['field'] as $delta => $value) {
      $form['field'][$delta] = array(
        '#type' => 'textfield',
        '#title' => 'Field ' . $delta,
        '#default_value' => $value,
        '#weight' => $weight++,
      );
    }
    $form['field']['new']['#weight'] = 999;
    $form['field']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Add',
      '#limit_validation_errors' => array(array('field')),
      '#submit' => array('::fieldSubmitForm'),
      '#weight' => 1000,
    );

    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => 'Published',
      '#default_value' => $stored_record['status'],
      // For simplicity, re-use Mollom module's administration permission.
      '#access' => \Drupal::currentUser()->hasPermission('administer mollom'),
    );
    return $form;
  }

  /**
   * Form element submit handler for mollom_test_form().
   */
  function fieldSubmitForm(array &$form, FormStateInterface $form_state) {
    // Remove all empty values of the multiple value field.
    $form_state->setValue('field', array_filter($form_state->getValue('field')));
    // Update the storage with submitted values.
    $storage_record = $form_state->getValues();
    // Store the new value and clear out the 'new' field.
    $new_field = $form_state->getValue(array('field','new'), '');
    if (!empty($new_field)) {
      $storage_record['field'][] = $form_state->getValue(array('field', 'new'));
      $form_state->setValue(array('field', 'new'), '');
      $storage_record['field']['new'] = '';
      unset($storage_record['field']['add']);
      $input = $form_state->getUserInput();
      $input['field']['new'] = '';
      $form_state->setUserInput($input);
    }
    $form_state->set('mollom_test', $storage_record);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Form submit handler for mollom_test_form().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Conditionally enable form caching.
    if (\Drupal::state()->get('mollom_test.cache_form', FALSE)) {
      $form_state->setCached(TRUE);
    }

    $new_field = $form_state->getValue(array('field','new'), '');
    if (!empty($new_field)) {
      $field = $form_state->getValue('field');
      $field[] = $new_field;
      $form_state->setValue('field', $field);
    }
    parent::submitForm($form, $form_state);

    drupal_set_message('Successful form submission.');
  }

  /**
   * {@inheritDoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    // Redirect to stored entry.
    $form_state->setRedirect('entity.mollom_test_post.edit_form', array('mollom_test_post' => $this->entity->id()));
  }
}
