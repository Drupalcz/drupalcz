<?php

namespace Drupal\mollom_test\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Mollom test post submission entity.
 *
 * @ContentEntityType(
 *   id = "mollom_test_post",
 *   label = @Translation("Mollom test post"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mollom_test\Form\PostForm",
 *       "edit" = "Drupal\mollom_test\Form\PostForm",
 *       "delete" = "Drupal\mollom_test\Form\PostDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
     base_table = "mollom_test_post",
 *   admin_permission = "access content",
 *   entity_keys = {
 *     "id" = "mid",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   }
 * )
 */

class Post extends ContentEntityBase implements PostInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->get('body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody($body) {
    $this->set('body', $body);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * Generate a storage record based on the entity data.
   */
  public function getStorageRecord() {
    return array(
      'mid' => $this->id(),
      'title' => $this->getTitle(),
      'body' => $this->getBody(),
      'status' => $this->getStatus(),
    );
  }

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['mid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('Unique mollom_test entity ID'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the post test entity'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Title of the post'))
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setRequired(TRUE);

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Body'))
      ->setDescription(t('Body of the post'))
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('Publishing status'))
      ->setDefaultValue(1)
      ->setSetting('size', 'tiny')
      ->setRequired(TRUE);

    $fields['readonly'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Read only field'))
      ->setDescription(t('This field should not be included in protectable fields.'))
      ->setReadOnly(TRUE);

    $fields['computed'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Computed field'))
      ->setDescription(t('This field should not be included in protectable fields.'))
      ->setComputed(TRUE);

    return $fields;
  }
}
