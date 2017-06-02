<?php
/**
 * A service factory to determine the correct version of the Drupal client
 * service.
 *
 * This will return the DrupalClient or a test service depending on if testing
 * mode is enabled.
 */

namespace Drupal\mollom\API;

use Drupal\Core\Config\ConfigFactory;
use Drupal\mollom\API\DrupalTestClient;
use Drupal\mollom\API\DrupalTestLocalClient;
use GuzzleHttp\ClientInterface;

class DrupalClientFactory {

  /**
   * Factory method to select the correct Mollom client service.
   *
   * @param ConfigFactory $config_factory
   *   The configuration factory in order to retrieve Mollom settings data.
   * @param ClientInterface $http_client
   *   An http client
   * @return DrupalClientInterface
   */
  public static function createDrupalClient(ConfigFactory $config_factory, ClientInterface $http_client) {
    $mollom_settings = $config_factory->get('mollom.settings');
    $state = \Drupal::state();
    if ($state->get('mollom.testing_use_local_invalid') ?: FALSE) {
      return new DrupalTestInvalid($config_factory, $http_client);
    }
    else if ($state->get('mollom.testing_use_local') ?: FALSE) {
      return new DrupalTestLocalClient($config_factory, $http_client);
    } else if ($mollom_settings->get('test_mode.enabled')) {
      return new DrupalTestClient($config_factory, $http_client);
    }
    else {
      return new DrupalClient($config_factory, $http_client);
    }
  }
}
