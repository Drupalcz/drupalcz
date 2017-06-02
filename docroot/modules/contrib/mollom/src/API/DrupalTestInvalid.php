<?php

namespace Drupal\mollom\API;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;

/**
 * Class DrupalTestInvalid
 * @package Drupal\mollom\API
 *
 * Drupal Mollom client implementation of an invalid server.
 */
class DrupalTestInvalid extends DrupalTestClient {
  public $createKeys = FALSE;

  private $currentAttempt = 0;
  private $originalServer;

  /**
   * Overrides MollomDrupalTest::__construct().
   */
  public function __construct(ConfigFactory $config_factory, ClientInterface $http_client) {
    parent::__construct($config_factory, $http_client);
    $this->originalServer = $this->server;
    $this->configuration_map['server'] = 'test_mode.invalid.api_endpoint';
    $this->saveConfiguration('server', 'fake-host');
  }

  /**
   * Override Mollom::query().
   */
  public function query($method, $path, array $data = [], array $expected = []) {
    $this->currentAttempt = 0;
    return parent::query($method, $path, $data, $expected);
  }

  /**
   * Overrides Mollom::handleRequest().
   *
   * Mollom::$server is replaced with an invalid server, so all requests will
   * result in a network error. However, if the 'mollom_testing_server_failover'
   * variable is set to TRUE, then the last request attempt will succeed.
   */
  protected function handleRequest($method, $server, $path, $data, $expected = []) {
    $this->currentAttempt++;

    if (\Drupal::state()->get('mollom_testing_server_failover', FALSE) && $this->currentAttempt == $this->requestMaxAttempts) {
      $server = strtr($server, [$this->server => $this->originalServer]);
    }
    return parent::handleRequest($method, $server, $path, $data, $expected);
  }

}
