<?php

namespace Drupal\dcz_apd\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining APD membership entities.
 *
 * @ingroup dcz_apd
 */
interface ApdMembershipInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the APD membership name.
   *
   * @return string
   *   Name of the APD membership.
   */
  public function getName();

  /**
   * Sets the APD membership name.
   *
   * @param string $name
   *   The APD membership name.
   *
   * @return \Drupal\dcz_apd\Entity\ApdMembershipInterface
   *   The called APD membership entity.
   */
  public function setName($name);

  /**
   * Gets the APD membership creation timestamp.
   *
   * @return int
   *   Creation timestamp of the APD membership.
   */
  public function getCreatedTime();

  /**
   * Sets the APD membership creation timestamp.
   *
   * @param int $timestamp
   *   The APD membership creation timestamp.
   *
   * @return \Drupal\dcz_apd\Entity\ApdMembershipInterface
   *   The called APD membership entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the APD membership published status indicator.
   *
   * Unpublished APD membership are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the APD membership is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a APD membership.
   *
   * @param bool $published
   *   TRUE to set this APD membership to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\dcz_apd\Entity\ApdMembershipInterface
   *   The called APD membership entity.
   */
  public function setPublished($published);

  /**
   * Gets the APD membership revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the APD membership revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\dcz_apd\Entity\ApdMembershipInterface
   *   The called APD membership entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the APD membership revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the APD membership revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\dcz_apd\Entity\ApdMembershipInterface
   *   The called APD membership entity.
   */
  public function setRevisionUserId($uid);

}
