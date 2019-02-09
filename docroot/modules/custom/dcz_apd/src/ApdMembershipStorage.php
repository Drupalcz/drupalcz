<?php

namespace Drupal\dcz_apd;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\dcz_apd\Entity\ApdMembershipInterface;

/**
 * Defines the storage handler class for APD membership entities.
 *
 * This extends the base storage class, adding required special handling for
 * APD membership entities.
 *
 * @ingroup dcz_apd
 */
class ApdMembershipStorage extends SqlContentEntityStorage implements ApdMembershipStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ApdMembershipInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {apd_membership_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {apd_membership_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ApdMembershipInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {apd_membership_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('apd_membership_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
