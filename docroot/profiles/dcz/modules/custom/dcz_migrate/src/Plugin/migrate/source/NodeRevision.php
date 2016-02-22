<?php

/**
 * @file
 * Contains \Drupal\dcz_migrate\Plugin\migrate\source\NodeRevision.
 */

namespace Drupal\dcz_migrate\Plugin\migrate\source;

/**
 * Drupal 6 node revision source from database.
 *
 * @MigrateSource(
 *   id = "dcz_d6_node_revision"
 * )
 */
class NodeRevision extends Node {

  /**
   * The join options between the node and the node_revisions_table.
   */
  const JOIN = 'n.nid = nr.nid AND n.vid <> nr.vid';

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Use all the node fields plus the vid that identifies the version.
    return parent::fields() + array(
      'vid' => t('The primary identifier for this version.'),
      'log' => $this->t('Revision Log message'),
      'timestamp' => $this->t('Revision timestamp'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    $ids['vid']['alias'] = 'nr';
    return $ids;
  }

}
