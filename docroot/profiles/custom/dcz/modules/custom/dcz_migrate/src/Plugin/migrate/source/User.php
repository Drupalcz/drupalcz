<?php

/**
 * @file
 * Contains \Drupal\dcz_migrate\Plugin\migrate\source\User.
 */

namespace Drupal\dcz_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 user source from database.
 *
 * @MigrateSource(
 *   id = "dcz_d6_user"
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
   * {@inheritdoc}
   */
  public function fields() {
    $fields = $this->baseFields();

    // Add roles field.
    $fields['roles'] = $this->t('Roles');

    // Profile fields.
    if ($this->moduleExists('profile')) {
      $fields += $this->select('profile_fields', 'pf')
        ->fields('pf', array('name', 'title'))
        ->execute()
        ->fetchAllKeyed();
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $uid = $row->getSourceProperty('uid');

    // User roles.
    $roles = $this->select('users_roles', 'ur')
      ->fields('ur', array('rid'))
      ->condition('ur.uid', $row->getSourceProperty('uid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('roles', $roles);

    // We are adding here the Event contributed module column.
    // @see https://api.drupal.org/api/drupal/modules%21user%21user.install/function/user_update_7002/7
    if ($row->hasSourceProperty('timezone_id') && $row->getSourceProperty('timezone_id')) {
      if ($this->getDatabase()->schema()->tableExists('event_timezones')) {
        $event_timezone = $this->select('event_timezones', 'e')
          ->fields('e', array('name'))
          ->condition('e.timezone', $row->getSourceProperty('timezone_id'))
          ->execute()
          ->fetchField();
        if ($event_timezone) {
          $row->setSourceProperty('event_timezone', $event_timezone);
        }
      }
    }

    // Name & surname.
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
      $row->setSourceProperty('dcz6_name', array_shift($names));
      $row->setSourceProperty('dcz6_surname', implode($names));
    }
    // Latitude & longitude.
    $result = $this->getDatabase()->query('
      SELECT
        loc.latitude,
        loc.longitude
      FROM
        {location} loc
      LEFT JOIN {location_instance} lic ON lic.lid=loc.lid
      WHERE
        lic.uid = :uid
    ', array(':uid' => $uid));
    foreach ($result as $record) {
      $row->setSourceProperty('dcz6_lat', $record->latitude);
      $row->setSourceProperty('dcz6_long', $record->longitude);
    }

    // Bio.
    $info = unserialize($row->getSourceProperty('data'));
    if (isset($info['info'])) {
      $row->setSourceProperty('dcz6_bio_value', $info['info']);
    } else{
      $row->setSourceProperty('dcz6_bio_value', '');
    }


    // Unserialize Data.
    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));

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
      'signature' => $this->t('Signature'),
      'signature_format' => $this->t('Signature format'),
      'created' => $this->t('Registered timestamp'),
      'access' => $this->t('Last access timestamp'),
      'login' => $this->t('Last login timestamp'),
      'status' => $this->t('Status'),
      'timezone' => $this->t('Timezone'),
      'language' => $this->t('Language'),
      'picture' => $this->t('Picture'),
      'init' => $this->t('Init'),
      'data' => $this->t('User data'),
    );

    // Possible field added by Date contributed module.
    // @see https://api.drupal.org/api/drupal/modules%21user%21user.install/function/user_update_7002/7
    if ($this->getDatabase()->schema()->fieldExists('users', 'timezone_name')) {
      $fields['timezone_name'] = $this->t('Timezone (Date)');
    }

    // Possible field added by Event contributed module.
    // @see https://api.drupal.org/api/drupal/modules%21user%21user.install/function/user_update_7002/7
    if ($this->getDatabase()->schema()->fieldExists('users', 'timezone_id')) {
      $fields['timezone_id'] = $this->t('Timezone (Event)');
    }

    return $fields;
  }

}
