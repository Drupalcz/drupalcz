<?php

/**
 * @file
 * Contains \Drupal\migrate_example\Plugin\migrate\source\BeerComment.
 */

namespace Drupal\migrate_example\Plugin\migrate\source;

/**
 * Drupal 6 comment source from database.
 *
 * @MigrateSource(
 *   id = "beer_comment"
 * )
 */
class BeerComment extends MigrateExampleSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('migrate_example_beer_comment', 'mec')
                 ->fields('mec', array('cid', 'cid_parent', 'name', 'mail', 'aid',
                   'body', 'bid', 'subject'))
                 ->orderBy('cid_parent', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'cid' => $this->t('Comment ID'),
      'cid_parent' => $this->t('Parent comment ID in case of comment replies'),
      'name' => $this->t('Comment name (if anon)'),
      'mail' => $this->t('Comment email (if anon)'),
      'aid' => $this->t('Account ID (if any)'),
      'bid' => $this->t('Beer ID that is being commented upon'),
      'subject' => $this->t('Comment subject'),
    );

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'cid' => array(
        'type' => 'integer',
        'alias' => 'mec',
      ),
    );
  }

}
