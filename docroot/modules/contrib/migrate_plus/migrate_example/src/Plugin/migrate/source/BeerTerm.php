<?php

/**
 * @file
 * Contains \Drupal\migrate_example\Plugin\migrate\source\BeerTerm.
 */

namespace Drupal\migrate_example\Plugin\migrate\source;

/**
 * Drupal 6 user source from database.
 *
 * @MigrateSource(
 *   id = "beer_term"
 * )
 */
class BeerTerm extends MigrateExampleSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('migrate_example_beer_topic', 'met')
      ->fields('met', array('style', 'details', 'style_parent', 'region',
                            'hoppiness'))
      ->orderBy('style_parent', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'style' => $this->t('Account ID'),
      'details' => $this->t('Blocked/Allowed'),
      'style_parent' => $this->t('Registered date'),
      'region' => $this->t('Account name (for login)'),
      'hoppiness' => $this->t('Account name (for display)'),
    );

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'style' => array(
        'type' => 'string',
        'alias' => 'met',
      ),
    );
  }

}
