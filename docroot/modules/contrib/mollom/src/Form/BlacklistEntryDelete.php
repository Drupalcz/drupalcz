<?php
/**
 * Delete form for a blacklist entry.
 */

namespace Drupal\mollom\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\mollom\Storage\BlacklistStorage;

class BlacklistEntryDelete extends ConfirmFormBase {

  /**
   * The blacklist entry being manipulated by this form.
   *
   * @var $entry
   */
  protected $entry;

  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entry_id = NULL) {
    try {
      $this->entry = BlacklistStorage::getEntry($entry_id);
    } catch (\Exception $e) {
      drupal_set_message(t('There was an error loading the entry to delete.'));
      return array();
    }
    if (empty($this->entry)) {
      drupal_set_message(t('There was an error loading the entry to delete.'));
      return array();
    }
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %term entry from the @type blacklist?', array(
      '%term' => $this->entry['value'],
      '@type' => $this->entry['reason'],
    ));
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return Url::fromRoute('mollom.blacklist.list');
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'blacklist_entry_delete_confirm';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the entry.
    BlacklistStorage::deleteEntry($this->entry['id']);

    // Set a message that the entry was deleted.
    drupal_set_message(t('The blacklist entry %term was deleted from the @type blacklist.', array(
      '%term' => $this->entry['value'],
      '@type' => $this->entry['reason'],
    )));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirect('mollom.blacklist.list');
  }
}
