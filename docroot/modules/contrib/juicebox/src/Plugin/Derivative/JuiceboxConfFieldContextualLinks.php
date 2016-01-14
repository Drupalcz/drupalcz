<?php

/**
 * @file
 * Contains \Drupal\juicebox\Plugin\Derivative\JuiceboxConfFieldContextualLinks.
 */

namespace Drupal\juicebox\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides dynamic contextual links for Juicebox field conf editing.
 */
class JuiceboxConfFieldContextualLinks extends DeriverBase implements ContainerDeriverInterface {

  // We'll use the injected entity manager service to calculate contextual
  // links.
  protected $entityManager;

  /**
   * Constructor
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    // Create a new instance of the deriver. This also allows us to extract
    // services from the container and inject them into our deriver via its own
    // constructor as needed.
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // We need a contextual link defined for each entity type (that may contain
    // a Juicebox gallery) in order to provide a link to the relevant edit
    // display screen. These link definitions must be unique because the related
    // route to the edit display screen is different for each entity type.
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Only fieldable entity are candidates.
      if ($entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
        $bundle_entity_type = $entity_type->getBundleEntityType();
        $type_name = $bundle_entity_type == 'bundle' ? $entity_type_id : $bundle_entity_type;
        $this->derivatives['juicebox.conf_field_' . $entity_type_id]['title'] = t('Configure galleries of this field instance');
        $this->derivatives['juicebox.conf_field_' . $entity_type_id]['route_name'] = 'entity.entity_view_display.' . $entity_type_id . '.view_mode';
        $this->derivatives['juicebox.conf_field_' . $entity_type_id]['group'] = 'juicebox_conf_field_' . $entity_type_id;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
