<?php

/**
 * @file
 * Contains Drupal\dropguard\Controller\RequestController.
 */

namespace Drupal\dropguard\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Class RequestController.
 *
 * @package Drupal\dropguard\Controller
 */
class RequestController extends ControllerBase {

  /**
   * Handles processing of the request.
   *
   * Contains the client_id and type route arguments,
   * as well as the $_POST data.
   *
   * @param object $request
   *   A Request class object.
   *
   * @return object
   *   A Json object with the result of the request, as expected by the
   *   Drop Guard app.
   */
  public function request(Request $request) {

    // Prevent this page from caching by Drupal.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Prepare the variables from the route data.
    $route_match = \Drupal::routeMatch();

    $type = $route_match->getParameter('type');

    // If Drop Guard wants only to check connection status, then we don't have
    // to expose any data. And access already checked in page access callback.
    if ($type == 'connect') {
      return new JsonResponse(TRUE);
    }
    else {

      // Get setting which provides information about way to update distribution
      // if it is enabled in this project. We've checked that this value exists
      // during access callback for the current page.
      $distribution_update = $request->request->get('distribution_update');

      // Get information about currently enabled
      // modules/themes/core/distribution.
      $data = dropguard_enabled_projects_info($distribution_update);

      $data_encrypted = dropguard_encrypt($data);

      return new JsonResponse($data_encrypted);
    }
  }

}
