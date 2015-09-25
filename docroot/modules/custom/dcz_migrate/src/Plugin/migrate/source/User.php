<?php

/**
 * @file
 * Contains \Drupal\dcz_migrate\Plugin\migrate\source\User.
 */

namespace Drupal\dcz_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Extract users from Drupal 7 database.
 *
 * @MigrateSource(
 *   id = "dcz_user"
 * )
 */
class User extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('users', 'u')
      ->fields('u', array_keys($this->baseFields()))
      ->condition('uid', 0, '>');
  }

  /**
   * Returns the user base fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function baseFields() {
    $fields = array(
      'uid' => $this->t('User ID'),
      'name' => $this->t('Username'),
      'pass' => $this->t('Password'),
      'mail' => $this->t('Email address'),
      'created' => $this->t('Registered timestamp'),
      'access' => $this->t('Last access timestamp'),
      'login' => $this->t('Last login timestamp'),
      'status' => $this->t('Status'),
      'timezone' => $this->t('Timezone'),
      'picture' => $this->t('Picture'),
      'init' => $this->t('Init'),
      'data' => $this->t('Data'),
    );
    return $fields;

  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = $this->baseFields();
    $fields['dcz_name'] = $this->t('Name');
    $fields['dcz_surname'] = $this->t('Surname');
    $fields['dcz_bio'] = $this->t('Bio');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $uid = $row->getSourceProperty('uid');

    // name & surname
    $result = $this->getDatabase()->query('
      SELECT
        prf.value
      FROM
        {profile_values} prf
      WHERE
        prf.uid = :uid
        AND prf.fid=1
    ', array(':uid' => $uid));
    foreach ($result as $record) {
      $names = explode(' ', $record->value, 2);
      $row->setSourceProperty('dcz_name', $names[0]);
      $row->setSourceProperty('dcz_surname', $names[1]);
    }

    // bio
    $info = unserialize($row->getSourceProperty('data'));
    $row->setSourceProperty('dcz_bio_value', $info['info']);
    $row->setSourceProperty('dcz_bio_format', 1);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'uid' => array(
        'type' => 'integer',
        'alias' => 'u',
      ),
    );
  }

//  /**
//   * {@inheritdoc}
//   */
//  public function entityTypeId() {
//    return 'user';
//  }

}

?>