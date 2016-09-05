<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base plugin to create DS post date plugins.
 */
abstract class Date extends DsFieldBase {

  /**
   * The EntityDisplayRepository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Display Suite field plugin.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $field = $this->getFieldConfiguration();
    $date_format = str_replace('ds_post_date_', '', $field['formatter']);
    $render_key = $this->getRenderKey();

    return array(
      '#markup' => format_date($this->entity()->{$render_key}->value, $date_format),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formatters() {
    $date_types = $this->entityTypeManager->getStorage('date_format')
      ->loadMultiple();

    $date_formatters = array();
    foreach ($date_types as $machine_name => $value) {
      /* @var $value DateFormatInterface */
      if ($value->isLocked()) {
        continue;
      }
      $date_formatters['ds_post_date_' . $machine_name] = t($value->id());
    }

    return $date_formatters;
  }

  /**
   * Returns the entity render key for this field.
   */
  public function getRenderKey() {
    return '';
  }

}
