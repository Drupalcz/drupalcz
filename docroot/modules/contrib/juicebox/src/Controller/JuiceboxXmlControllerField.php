<?php

/**
 * @file
 * Controller routines for field-based XML.
 */

namespace Drupal\juicebox\Controller;

use Drupal\juicebox\JuiceboxGalleryInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;


/**
 * Controller routines for field-based XML.
 */
class JuiceboxXmlControllerField extends JuiceboxXmlControllerBase {

  // Base properties that reference source data.
  protected $entityType;
  protected $entityId;
  protected $fieldName;
  protected $displayName;
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function init() {
    // Collect the args from the path manually. We can't pass them in from the
    // controller base because that class has to work for a variable number
    // of args.
    $attribs = $this->request->attributes->get('_raw_variables');
    // Set data sources as properties.
    $this->entityType = $attribs->get('entityType');
    $this->entityId = $attribs->get('entityId');
    $this->fieldName = $attribs->get('fieldName');
    $this->displayName = $attribs->get('displayName');
    // Grab the loaded entity as well.
    $this->entity = $this->entityManager->getStorage($this->entityType)->load($this->entityId);
    if (is_object($this->entity) && $this->entity instanceof EntityInterface) {
      // All looks good.
      return;
    }
    throw new \Exception(t('Cannot instantiate field-based Juicebox gallery as no entity can be loaded.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function access() {
    // Drupal 8 has unified APIs for access checks so this is pretty easy.
    if (is_object($this->entity)) {
      $entity_access = $this->entity->access('view');
      $field_access = $this->entity->{$this->fieldName}->access('view');
      return ($entity_access && $field_access);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getGallery() {
    // Build the field and gallery.
    $field = $this->entity->{$this->fieldName}->view($this->displayName);
    // Make sure that the Juicebox is actually built.
    if (!empty($field[0]['#gallery']) && $field[0]['#gallery'] instanceof JuiceboxGalleryInterface && $field[0]['#gallery']->getId()) {
      return $field[0]['#gallery'];
    }
    throw new \Exception(t('Cannot build Juicebox XML for field-based gallery.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function calculateXmlCacheTags() {
    // Add tags for the entity that this XML comes from.
    $entity_tags = (is_object($this->entity) && $this->entity instanceof CacheableDependencyInterface) ? $this->entity->getCacheTags() : array();
    // Also fetch the tags from the display configuration as that is where our
    // gallery-specific settings are stored (so changes there should also
    // invalidate the XML).
    $display = entity_get_display($this->entityType, $this->entity->bundle(), $this->displayName);
    $display_tags = array();
    if ($display instanceof CacheableDependencyInterface) {
      $display_tags = $display->getCacheTags();
      // If this is not a custom display then we need to also include the
      // default display cache tags as Drupal may reference this display
      // elsewhere by the "default" label.
      if (!$display->status() || $display->isNew()) {
        $display_default = entity_get_display($this->entityType, $this->entity->bundle(), 'default');
        if ($display_default instanceof CacheableDependencyInterface) {
          $display_tags = Cache::mergeTags($display_tags, $display_default->getCacheTags());
        }
      }
    }
    return Cache::mergeTags($entity_tags, $display_tags);
  }

}
