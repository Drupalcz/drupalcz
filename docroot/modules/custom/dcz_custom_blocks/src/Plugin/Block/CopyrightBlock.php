<?php

namespace Drupal\dcz_custom_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base for custom copyright block.
 *
 * @Block(
 *   id = "dcz_custom_blocks_copyright",
 *   admin_label = @Translation("Copyright block")
 * )
 */
class CopyrightBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'copyright_custom' => "",
      // @ToDo: Fill correct default value here!
      'copyright_default' => $this->t("Copyright text"),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['copyright_custom_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom copyright text'),
      '#description' => $this->t('Let this field empty to use default value'),
      '#default_value' => $this->configuration['copyright_custom'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['copyright_custom']
      = $form_state->getValue('copyright_custom_value');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $text = $this->configuration['copyright_default'];
    if (!empty($this->configuration['copyright_custom'])) {
      $text = $this->configuration['copyright_custom'];
    }
    return [
      '#type' => 'markup',
      '#markup' => $text,
    ];
  }

}
