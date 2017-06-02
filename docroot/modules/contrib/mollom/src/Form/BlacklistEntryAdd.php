<?php
/**
 * Created by PhpStorm.
 * User: lisa.backer
 * Date: 7/3/14
 * Time: 1:09 PM
 */

namespace Drupal\mollom\Form;


use Drupal\Core\Form\FormStateInterface;

class BlacklistEntryAdd extends BlacklistEntryFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mollom_blacklist_add';
  }

  /*
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entry_id = NULL) {
    $form = parent::buildForm($form, $form_state, $entry_id);
    $form['actions']['submit']['#value'] = $this->t('Add blacklist entry');
    return $form;
  }
} 
