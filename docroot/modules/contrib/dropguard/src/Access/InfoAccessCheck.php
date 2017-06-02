<?php

/**
 * @file
 * Contains \Drupal\dropguard\Access\InfoAccessCheck.
 */

namespace Drupal\dropguard\Access;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Access check for requesting the website data.
 */
class InfoAccessCheck implements AccessInterface {

  /**
   * Checks access for the dropguard.request_url route.
   *
   * @param object $request
   *  A Request class object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request) {

    // Get the data from the route.
    $route_match = \Drupal::routeMatch();

    $client_id = $route_match->getParameter('client_id');
    $type = $route_match->getParameter('type');

    // Get the data from $_POST.
    $public_key = $request->request->get('public_key');
    $distribution_update = $request->request->get('distribution_update');

    if (!is_numeric($client_id) || !is_string($type)) {
      \Drupal::logger('dropguard')->notice('Wrong <em>URL</em> parameters requested.');
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }
    elseif (!in_array($type, array('connect', 'modules'))) {
      \Drupal::logger('dropguard')->notice('Wrong <em>type</em> parameter requested.');
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }
    elseif ((empty($public_key) || !isset($distribution_update))) {
      \Drupal::logger('dropguard')->notice('Public key or distribution update mode not specified.');
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }

    // Make sure that client ID and public keys from Drop Guard matches
    // credentials on this web site.
    $current_client_id = \Drupal::config('dropguard.settings')->get('dropguard.id');
    $current_public_key = \Drupal::config('dropguard.settings')->get('dropguard.key');

    if ($client_id != $current_client_id || $public_key != $current_public_key) {
      \Drupal::logger('dropguard')->notice('User ID or public key mismatch.');
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }

    // If openssl extension is disabled, then we can't allow Drop Guard to
    // get any data from this site, because it might be insecure.
    if (!extension_loaded('openssl')) {
      \Drupal::logger('dropguard')->notice('OpenSSL extension is disabled or not available.');
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }

    return AccessResult::allowed()->setCacheMaxAge(0);
  }
}
