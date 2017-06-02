<?php

namespace Drupal\mollom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mollom\Storage\BlacklistStorage;
use Drupal\mollom\Utility\MollomUtilities;

/**
 * Class BlacklistEntryFormBase
 *
 * Provides a base form for adding/editing a blacklist entry.
 *
 * @package Drupal\mollom\Form
 */
abstract class BlacklistEntryFormBase extends FormBase {

  /**
   * The blacklist entry being manipulated by this form.
   *
   * @var $entry
   */
  protected $entry;

  /**
   * Gets the blacklist entry.
   *
   * @return array
   *   The associative array of blacklist entry data.
   */
  public function getEntry() {
    return $this->entry;
  }

  /**
   * Helper function to populate the entry array based on the current id.
   *
   * This allows default values to be set if the entry id is empty.
   *
   * @param $entry_id
   *   The blacklist entry id.
   * @return $this
   *   A reference to the current class for chaining.
   */
  public function setEntryById($entry_id = NULL) {
    $entry = $this->loadByEntryId($entry_id);
    $this->setEntry($entry);
    return $this;
  }

  /**
   * Sets the current blacklist entry for the form.
   *
   * @param array $entry
   *   The associative array of entry data.
   *
   * @return $this
   *   A reference to the current class for chaining.
   */
  public function setEntry(array $entry) {
    if (!is_array($entry)) {
      $entry = array();
    }
    $defaults = array(
      'reason' => BlacklistStorage::TYPE_SPAM,
      'context' => BlacklistStorage::CONTEXT_ALL_FIELDS,
      'value' => '',
      'match' => BlacklistStorage::MATCH_CONTAINS,
    );
    $this->entry = array_merge($defaults, $entry);
    return $this;
  }

  /**
   * Overrides Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entry_id = NULL) {
    MollomUtilities::getAdminAPIKeyStatus();
    MollomUtilities::displayMollomTestModeWarning();

    $entry = $this->setEntryById($entry_id)->getEntry();
    $form['entry_id'] = array(
      '#type' => 'value',
      '#value' => $entry_id,
    );

    $form['reason'] = array(
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#default_value' => $entry['reason'],
      '#options' => $this->getBlacklistTypeOptions(),
      '#required' => TRUE,
    );

    $form['context'] = array(
      '#type' => 'select',
      '#title' => $this->t('Context'),
      '#default_value' => $entry['context'],
      '#options' => $this->getContextOptions(),
      '#required' => TRUE,
    );

    $form['match'] = array(
      '#type' => 'select',
      '#title' => $this->t('Matches'),
      '#default_value' => $entry['match'],
      '#options' => $this->getMatchesOptions(),
      '#required' => TRUE,
    );

    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $entry['value'],
      '#required' => TRUE,
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save entry'),
    );

    return $form;
  }

  /**
   * Overrides Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entry = array();
    $id = $form_state->getValue('entry_id', '');
    if (!empty($id)) {
      $entry['id'] = $id;
    }
    $entry['reason'] = $form_state->getValue('reason', BlacklistStorage::TYPE_SPAM);
    $entry['context'] = $form_state->getValue('context', BlacklistStorage::CONTEXT_ALL_FIELDS);
    $entry['match'] = $form_state->getValue('match', BlacklistStorage::MATCH_CONTAINS);
    $entry['value'] = $form_state->getValue('value', '');
    $saved = BlacklistStorage::saveEntry($entry);
    if ($saved) {
      drupal_set_message($this->t('The entry was added to the @type blacklist.', [
        '@type' => $entry['reason'],
      ]));
      // Redirect the user to the following path after the save action.
      $form_state->setRedirect('mollom.blacklist.list');
    }
    else {
      drupal_set_message($this->t('There was a problem saving the blacklist entry to your @type blacklist.', [
        '@type' => $entry['reason'],
      ]), 'error');
    }
  }

  /**
   * Generates the form options for blacklist entry types.
   *
   * @returns array
   *   An array suitable for use as select input options.
   */
  protected function getBlacklistTypeOptions() {
    return array(
      BlacklistStorage::TYPE_SPAM => $this->t('Spam'),
      BlacklistStorage::TYPE_PROFANITY => $this->t('Profanity'),
      BlacklistStorage::TYPE_UNWANTED => $this->t('Unwanted'),
    );
  }

  /**
   * Generates the form options for blacklist entry context.
   *
   * @returns array
   *   An array suitable for use as select input options.
   */
  protected function getContextOptions() {
    return array(
      BlacklistStorage::CONTEXT_ALL_FIELDS => $this->t('- All fields -'),
      BlacklistStorage::CONTEXT_AUTHOR_FIELDS => $this->t('- All author fields -'),
      BlacklistStorage::CONTEXT_AUTHOR_NAME => $this->t('Author name'),
      BlacklistStorage::CONTEXT_AUTHOR_MAIL => $this->t('Author e-mail'),
      BlacklistStorage::CONTEXT_AUTHOR_IP => $this->t('Author IP'),
      BlacklistStorage::CONTEXT_AUTHOR_ID => $this->t('Author User ID'),
      BlacklistStorage::CONTEXT_POST_FIELDS => $this->t('- All post fields -'),
      BlacklistStorage::CONTEXT_POST_TITLE => $this->t('Post title'),
      BlacklistStorage::CONTEXT_LINKS => $this->t('Links'),
    );
  }

  /**
   * Generates the form options for type of matching.
   *
   * @return array
   *   An array suitable for use as select input options.
   */
  protected function getMatchesOptions() {
    return array(
      BlacklistStorage::MATCH_CONTAINS => $this->t('Contains'),
      BlacklistStorage::MATCH_EXACT => $this->t('Exact'),
    );
  }

  /**
   * Loads a blacklist entry by id and saves it to the class variable.
   *
   * @param $entry_id
   *   The id of the blacklist entry
   * @return array
   *   The blacklist entry data (or data for a blank entry)
   */
  private function loadByEntryId($entry_id = NULL) {
    if (is_null($entry_id)) {
      return array();
    }
    $this->setEntry(BlacklistStorage::getEntry($entry_id));
    return $this->getEntry();
  }
}
