<?php

/**
 * @file
 * Controller routines for field-based XML.
 */

namespace Drupal\juicebox\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\juicebox\JuiceboxGalleryInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;


/**
 * Controller routines for field-based XML.
 */
class JuiceboxXmlControllerField extends JuiceboxXmlControllerBase {

  /**
   * The entity type involved in this XML request (e.g. "node").
   *
   * @var string
   */
  protected $entityType;

  /**
   * The numeric ID of the entity involved in this XML request.
   *
   * @var string
   */
  protected $entityId;

  /**
   * The field name of the entity field involved in this XML request.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The display name (view mode) on the entity involved in this XML request.
   *
   * @var string
   */
  protected $displayName;

  /**
   * The loaded entity involved in this XML request.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * A Drupal entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A Drupal entity respository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;


  /**
   * Factory to fetch required dependencies from container.
   */
  public static function create(ContainerInterface $container) {
    // Create the actual controller instance.
    return new static($container->get('config.factory'), $container->get('request_stack'), $container->get('http_kernel'), $container->get('entity_type.manager'), $container->get('entity.repository'));
  }

  /**
   * Constructor
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory that can be used to derive global Juicebox
   *   settings.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Symfony request stack from which to extract the current request.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The Symfony http kernel service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   A Drupal entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository;
   *   A Drupal entity repository service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, HttpKernelInterface $http_kernel, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct($config_factory, $request_stack, $http_kernel);
    // The field XML controller requires a couple extra services that are not
    // initiated in the base controller class.
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

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
    // Grab the loaded entity as well, translated appropriately.
    $entity_base = $this->entityTypeManager->getStorage($this->entityType)->load($this->entityId);
    $this->entity = $this->entityRepository->getTranslationFromContext($entity_base);
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
    $entity_tags = $this->entity instanceof CacheableDependencyInterface ? $this->entity->getCacheTags() : array();
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
