<?php

namespace Drupal\dcz_apd;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ApdMembershipStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of APD membership revision IDs for a specific APD membership.
   *
   * @param \Drupal\dcz_apd\Entity\ApdMembershipInterface $entity
   *   The APD membership entity.
   *
   * @return int[]
   *   APD membership revision IDs (in ascending order).
   */
  public function revisionIds(ApdMembershipInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as APD membership author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   APD membership revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\dcz_apd\Entity\ApdMembershipInterface $entity
   *   The APD membership entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ApdMembershipInterface $entity);

  /**
   * Unsets the language for all APD membership with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
