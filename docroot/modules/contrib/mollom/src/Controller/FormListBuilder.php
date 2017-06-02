<?php

namespace Drupal\mollom\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\mollom\Entity\FormInterface;
use Drupal\mollom\Utility\MollomUtilities;

/**
 * Provides a listing of mollom_form entities.
 *
 * @package Drupal\mollom\Controller
 *
 * @ingroup mollom
 */
class FormListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    MollomUtilities::getAdminAPIKeyStatus();
    MollomUtilities::displayMollomTestModeWarning();

    $header['label'] = $this->t('Form');
    $header['protection_mode'] = $this->t('Protection mode');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $mollom_form = $entity->initialize();
    $row['label'] = $entity->label() . ' (' . $entity->id() . ')';
    if (isset($mollom_form['orphan'])) {
      $row['protection_mode'] = t('- orphan -');
    }
    else {
      if ($entity->mode == FormInterface::MOLLOM_MODE_ANALYSIS) {
        $row['protection_mode'] = t('Textual analysis (@discard)', [
          '@discard' => $entity->discard ? t('discard') : t('retain'),
        ]);
      }
      else {
        $row['protection_mode'] = t('CAPTCHA only');
      }
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $mollom_form = $entity->initialize();
    $operations = parent::getDefaultOperations($entity);
    if (!empty($mollom_form['orphan'])) {
      drupal_set_message(t("%module module's %form_id form no longer exists.", [
        '%form_id' => $entity->id(),
        '%module' => $entity->module,
      ]), 'warning');
      unset($operations['edit']);
    }
    $operations['delete']['title'] = t('Unprotect');
    return $operations;
  }

}
