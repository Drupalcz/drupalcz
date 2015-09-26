<?php

/**
 * @file
 * Contains \Drupal\migrate_example\Plugin\migrate\source\BeerUser.
 */

namespace Drupal\migrate_example\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Drupal 6 user source from database.
 *
 * @MigrateSource(
 *   id = "beer_user"
 * )
 */
class BeerUser extends MigrateExampleSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('migrate_example_beer_account', 'mea')
      ->fields('mea', array('aid', 'status', 'posted', 'name', 'nickname',
                            'password', 'mail', 'sex', 'beers'));
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'aid' => $this->t('Account ID'),
      'status' => $this->t('Blocked/Allowed'),
      'posted' => $this->t('Registered date'),
      'name' => $this->t('Account name (for login)'),
      'nickname' => $this->t('Account name (for display)'),
      'password' => $this->t('Account password (raw)'),
      'mail' => $this->t('Account email'),
      'sex' => $this->t('Gender'),
      'beers' => $this->t('Favorite beers, pipe-separated'),
    );

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'aid' => array(
        'type' => 'integer',
        'alias' => 'mea',
      ),
    );
  }

  public function prepareRow(Row $row) {
    if ($value = $row->getSourceProperty('beers')) {
      $row->setSourceProperty('beers', explode('|', $value));
    }
    return parent::prepareRow($row);
  }

}
