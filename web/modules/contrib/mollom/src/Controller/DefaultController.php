<?php

namespace Drupal\mollom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;

/**
 * Default controller for the mollom module.
 */
class DefaultController extends ControllerBase {

  /**
   * Menu access callback; Determine access to report to Mollom.
   *
   * The special $entity type "session" may be used for mails and messages, which
   * originate from form submissions protected by Mollom, and can be reported by
   * anyone; $id is expected to be a Mollom session id instead of an entity id
   * then.
   *
   * @param $entity
   *   The entity type of the data to report.
   * @param $id
   *   The entity id of the data to report.
   *
   * @todo Revamp this based on new {mollom}.form_id info.
   */
  function reportAccess($entity, $id) {
    // The special entity 'session' means that $id is a Mollom session_id, which
    // can always be reported by everyone.
    if ($entity == 'session') {
      return !empty($id) ? TRUE : FALSE;
    }
    // Retrieve information about all protectable forms. We use the first valid
    // definition, because we assume that multiple form definitions just denote
    // variations of the same entity (e.g. node content types).
    foreach (mollom_form_list() as $form_id => $info) {
      if (!isset($info['entity']) || $info['entity'] != $entity) {
        continue;
      }
      // If there is a 'report access callback', invoke it.
      if (isset($info['report access callback']) && function_exists($info['report access callback'])) {
        $function = $info['report access callback'];
        return $function($entity, $id);
      }
      // Otherwise, if there is a 'report access' list of permissions, iterate
      // over them.
      if (isset($info['report access'])) {
        foreach ($info['report access'] as $permission) {
          if (\Drupal::currentUser()->hasPermission($permission)) {
            return TRUE;
          }
        }
      }
    }
    // If we end up here, then the current user is not permitted to report this
    // content.
    return FALSE;
  }

  /**
   * Access callback; check if the module is configured.
   *
   * This function does not actually check whether Mollom keys are valid for the
   * site, but just if the keys have been entered.
   *
   * @param $permission
   *   An optional permission string to check with \Drupal::currentUser()->hasPermission().
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  function access($permission = FALSE) {
    $configured = \Drupal::config('mollom.settings')->get('keys.public') && \Drupal::config('mollom.settings')->get('keys.private');
    if ($configured && $permission) {
      return AccessResult::allowedIfHasPermission($permission, \Drupal::currentUser());
    }
    else {
      return AccessResult::allowedIf($configured);
    }
  }
}
