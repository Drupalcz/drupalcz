<?php

namespace Drupal\dcz_apd;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the APD membership entity.
 *
 * @see \Drupal\dcz_apd\Entity\ApdMembership.
 */
class ApdMembershipAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dcz_apd\Entity\ApdMembershipInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished apd membership entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published apd membership entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit apd membership entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete apd membership entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add apd membership entities');
  }

}
