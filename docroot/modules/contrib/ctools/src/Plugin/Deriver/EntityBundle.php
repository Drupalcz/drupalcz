<?php
/**
 * @file
 * Contains \Drupal\ctools\Plugin\Deriver\EntityBundle
 */

namespace Drupal\ctools\Plugin\Deriver;

use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Deriver that creates a condition for each entity type with bundles.
 */
class EntityBundle extends EntityDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasKey('bundle')) {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['label'] = $this->t('@label', ['@label' => $entity_type->getBundleLabel()]);
        $this->derivatives[$entity_type_id]['context'] = [
          "$entity_type_id" => new ContextDefinition('entity:' . $entity_type_id),
        ];
      }
    }
    return $this->derivatives;
  }

}
