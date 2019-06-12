<?php

namespace Drupal\dcz_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Represents DrupalCS Awards records as resources.
 *
 * @RestResource (
 *   id = "dcz_rest_drupalcs_awards",
 *   label = @Translation("DrupalCS Awards"),
 *   uri_paths = {
 *     "canonical" = "/api/dcz-rest-drupalcs-awards/{id}"
 *   }
 * )
 */
class DrupalCsAwardsResource extends ResourceBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Add `DrupalCS Awards 2019` tag.
   *
   * @param int $id
   *   Node ID.
   *
   * @return \Drupal\rest\ModifiedResourceResponse|\Symfony\Component\HttpFoundation\Response
   *   JSON response with 200 or 400 http response code.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function patch($id) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->entityTypeManager->getStorage('node')->load($id);
    if (is_null($node) || $node->bundle() !== 'dcz_showcase') {
      return new Response(json_encode(['message' => 'Invalid Node ID provided.']), 400);
    }

    $tags = $node->get('field_tags')->getValue();
    // Check if node already doesn't have tag.
    $found = FALSE;
    foreach ($tags as $tag) {
      if ($tag['target_id'] == 251) {
        $found = TRUE;
        break;
      }
    }

    // If node doesn't have tag then add it.
    if (!$found) {
      $tags[] = ['target_id' => 251];
      $node->set('field_tags', $tags);
      $node->save();
    }

    // Get main screenshot URL.
    /** @var \Drupal\file\Entity\File $file */
    $file = $node->field_main_screenshot->entity->field_media_image->entity;
    $coverUrl = $file->toUrl();

    return new Response(json_encode(['data' => ['image' => $coverUrl]]));
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);

    // Change ID validation pattern.
    if ($method != 'POST') {
      $route->setRequirement('id', '\d+');
    }

    return $route;
  }

}
