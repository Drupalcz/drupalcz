<?php

namespace Drupal\mollom;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\mollom\Controller\FormController;

/**
 * Class EntityReportAccessManager
 * @package Drupal\mollom
 */
class EntityReportAccessManager  {

  /**
   * Determines if the user specified has access to report the entity.
   *
   * @param \Drupal\core\Entity\EntityInterface $entity
   *   The entity to check access for
   * @param $form_id string
   *   The form that is protected for this entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to use.  If null, use the current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public static function accessReport($entity, $form_id, $account = NULL) {
    // Check if the user has access to this comment.
    $result = $entity->access('edit', $account, TRUE)
      ->andIf($entity->access('update', $account, TRUE));
    if (!$result->isAllowed()) {
      return $result;
    }
    // Check if this entity type is protected.
    $form_entity = \Drupal::entityManager()->getStorage('mollom_form')->load($form_id);
    if (empty($form_entity)) {
      return new AccessResultForbidden();
    }
    // Check any specific report access callbacks.
    $forms = FormController::getProtectableForms();
    $info = $forms[$form_id];
    if (empty($info)) {
      // Orphan form protection.
      return new AccessResultForbidden();
    }

    $report_access_callbacks = [];
    $access_permissions = [];

    // If there is a 'report access callback' add it to the list.
    if (isset($info['report access callback'])
      && function_exists($info['report access callback'])
      && !in_array($info['report access callback'], $report_access_callbacks)) {
      $report_access_callbacks[] = $info['report access callback'];
    }
    // Otherwise add any access permissions.
    else if (isset($info['report access']) && !in_array($info['report access'], $access_permissions)) {
      $access_permissions += $info['report access'];
    }

    foreach($report_access_callbacks as $callback) {
      if (!$callback($entity->getEntityTypeId(), $entity->id())) {
        return new AccessResultForbidden();
      }
    }

    foreach($access_permissions as $permission) {
      if (empty($account)) {
        $account = \Drupal::currentUser();
      }
      if (!$account->hasPermission($permission)) {
        return new AccessResultForbidden();
      }
    }
    return new AccessResultAllowed();
  }
}
