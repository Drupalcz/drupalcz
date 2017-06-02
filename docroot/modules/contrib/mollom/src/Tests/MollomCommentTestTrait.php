<?php

namespace Drupal\mollom\Tests;

use Drupal\comment\Entity\CommentType;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldConfig;

/**
 * When used together with Drupal\comment\Tests\CommentTestTrait this provides the
 * common functionality for testing comment form protection.
 */
trait MollomCommentTestTrait {

  use CommentTestTrait;

  /**
   * Sets a comment settings variable for the article content type.
   *
   * @param string $name
   *   Name of variable.
   * @param string $value
   *   Value of variable.
   * @param string $field_name
   *   (optional) Field name through which the comment should be posted.
   *   Defaults to 'comment'.
   *
   * @see Drupal\comment\Tests\CommentTestBase.
   */
  public function setCommentSettings($name, $value, $field_name = 'comment') {
    $field = FieldConfig::loadByName('node', 'article', $field_name);
    $field->setSetting($name, $value);
    $field->save();
  }

  /**
   * Loads comments based on the comment subject.
   *
   * @param string $subject
   *   The subject to search for
   * @return array
   *   An array of comment ids that match the subject.
   */
  public function loadCommentsBySubject($subject) {
    $entity_query = \Drupal::entityQuery('comment');
    $entity_query->condition('subject', $subject);
    return $entity_query->execute();
  }

  /**
   * Add comments to an entity type.
   *
   * @param string $bundle
   *   The node type to add comments on.
   * @param int $preview
   *   How to configure comment preview.  Acceptable values are DRUPAL_OPTIONAL,
   *   DRUPAL_REQUIRED, DRUPAL_DISABLED
   * @param string $comment_type_id
   *   The type of comment entity to add to this node.
   */
  public function addCommentsToNode($bundle = 'article', $preview = DRUPAL_OPTIONAL, $comment_type_id = 'comment') {
    // While it seems wrong to add the $comment_type_id as the field name, it
    // is the only way that the comment test trait uses the correct
    // comment type to simulate the correct form id.
    // @see https://www.drupal.org/node/2622440
    $this->addDefaultCommentField('node', $bundle, $comment_type_id, CommentItemInterface::OPEN, $comment_type_id);
    $this->setCommentSettings('preview', $preview);
  }

  /**
   * Creates a comment comment type (bundle).
   *
   * @param string $label
   *   The comment type label.
   *
   * @return \Drupal\comment\Entity\CommentType
   *   Created comment type.
   *
   * @see \Drupal\comment\Tests\CommentTestBase
   */
  public function createCommentType($label) {
    $bundle = CommentType::create(array(
      'id' => $label,
      'label' => $label,
      'description' => '',
      'target_entity_type_id' => 'node',
    ));
    $bundle->save();
    return $bundle;
  }
}
