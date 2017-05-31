<?php

/**
 * @file
 * Contains \Drupal\dcz_custom_blocks\Plugin\Block\TextBlock.
 */

namespace Drupal\dcz_custom_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @Block(
 *   id = "dcz_custom_blocks_text",
 *   admin_label = @Translation("Custom text block")
 * )
 */
class TextBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'value' => "",
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['value_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Custom text'),
      '#default_value' => $this->configuration['value'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['value']
      = $form_state->getValue('value_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->configuration['value'],
    );
  }

}
