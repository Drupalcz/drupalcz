<?php
/**
 * Drupal-specific implementation of the Mollom PHP library.
 */

namespace Drupal\mollom\API;

if (!class_exists("Mollom\Client\Client")) {
  if (file_exists(drupal_get_path('module', 'mollom') . '/vendor/autoload.php')) {
    require_once drupal_get_path('module', 'mollom') . '/vendor/autoload.php';
  }
  else {
    // Class does not exist. Fail!
    throw new \Exception("Mollom Class could not be found. Please run composer in the module folder or install composer manager.");
  }
}

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\mollom\Utility\Logger;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use Mollom\Client\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;


class DrupalClient extends Client implements DrupalClientInterface {

  const MAX_EXPECTED_LANGUAGES_LENGTH = 64;

  /**
   * The settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  public $config;

  /**
   * The HTTP client.
   */
  public $client;

  /**
   * Mapping of configuration names to Drupal variables.
   *
   * @var array
   *
   * @see Mollom::loadConfiguration()
   */
  protected $configuration_map = array(
    'publicKey' => 'keys.public',
    'privateKey' => 'keys.private',
    'expectedLanguages' => 'languages_expected',
    'server' => 'api_endpoint',
  );

  /**
   * Overrides the connection timeout based on module configuration.
   *
   * Constructor.
   * @param ConfigFactory $config_factory
   * @param ClientInterface $http_client
   *
   * @see Mollom::__construct().
   */
  public function __construct(ConfigFactory $config_factory, ClientInterface $http_client) {
    $this->config = $config_factory->getEditable('mollom.settings');
    $this->requestTimeout = $this->config->get('connection_timeout_seconds');
    $this->client = $http_client;
    parent::__construct();
    // Set any configured server that may be different from the default.
    $configured_server = $this->loadConfiguration('server');
    if (!empty($configured_server)) {
      $this->saveConfiguration('server', $configured_server);
    }
    $this->requestTimeout = $this->config->get('connection_timeout_seconds');
  }

  /**
   * Factory method for DrupalClient.
   *
   * When Drupal builds this class it does not call the constructor directly.
   * Instead, it relies on this method to build the new object. Why? The class
   * constructor may take multiple arguments that are unknown to Drupal. The
   * create() method always takes one parameter -- the container. The purpose
   * of the create() method is twofold: It provides a standard way for Drupal
   * to construct the object, meanwhile it provides you a place to get needed
   * constructor parameters from the container.
   *
   * In this case, we ask the container for an config.factory factory and a http_client. We then
   * pass the factory and the http client to our class as a constructor parameter.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('http_client'));
  }

  /**
   * Implements Mollom::loadConfiguration().
   */
  public function loadConfiguration($name) {
    $name = $this->configuration_map[$name];
    return $this->config->get($name);
  }

  /**
   * Implements Mollom::saveConfiguration().
   */
  public function saveConfiguration($name, $value) {
    // Save it to the class properties if applicable.
    if (property_exists('\Drupal\mollom\API\DrupalClient', $name)) {
      $this->{$name} = $value;
    }
    // Persist in Drupal too.
    $name = $this->configuration_map[$name];
    $this->config->set($name, $value)->save();
  }

  /**
   * Implements Mollom::deleteConfiguration().
   */
  public function deleteConfiguration($name) {
    $name = $this->configuration_map[$name];
    $this->config->clear($name)->save();
  }

  /**
   * Implements Mollom::getClientInformation().
   */
  public function getClientInformation() {
    // Retrieve Drupal distribution and installation profile information.
    $profile = drupal_get_profile();
    $profile_info = system_get_info('module', $profile) + array(
      'distribution_name' => 'Drupal',
      'version' => \Drupal::VERSION,
    );

    // Retrieve Mollom module information.
    $mollom_info = system_get_info('module', 'mollom');
    if (empty($mollom_info['version'])) {
      // Manually build a module version string for repository checkouts.
      $mollom_info['version'] = \Drupal::CORE_COMPATIBILITY . '-1.x-dev';
    }

    $data = array(
      'platformName' => $profile_info['distribution_name'],
      'platformVersion' => $profile_info['version'],
      'clientName' => $mollom_info['name'],
      'clientVersion' => $mollom_info['version'],
    );
    return $data;
  }

  /**
   * Overrides Mollom::writeLog().
   */
  function writeLog() {
    foreach ($this->log as $entry) {
      $response = $entry['response'];
      $response = ($response instanceof Stream) ? $response->getContents() : $response;

      $entry['Request: ' . $entry['request']] = !empty($entry['data']) ? $entry['data'] : NULL;
      unset($entry['request'], $entry['data']);

      $entry['Request headers:'] = $entry['headers'];
      unset($entry['headers']);

      $entry['Response: ' . $entry['response_code'] . ' ' . $entry['response_message'] . ' (' . number_format($entry['response_time'], 3) . 's)'] = $response;
      unset($entry['response'], $entry['response_code'], $entry['response_message'], $entry['response_time']);

      // The client class contains the logic for recovering from certain errors,
      // and log messages are only written after that happened. Therefore, we
      // can normalize the severity of all log entries to the overall success or
      // failure of the attempted request.
      // @see Mollom::query()
      Logger::addMessage($entry, $this->lastResponseCode === TRUE ? NULL : RfcLogLevel::WARNING);
    }

    // After writing log messages, empty the log.
    $this->purgeLog();
  }

  /**
   * Implements Mollom::request().
   */
  protected function request($method, $server, $path, $query = NULL, array $headers = array()) {
    $options = array(
      'timeout' => $this->requestTimeout,
    );
    if (isset($query)) {
      if ($method === 'GET') {
        $path .= '?' . $query;
      }
      else {
        $options['body'] = $query;
      }
    }
    $request = new Request($method, $server . '/' . $path, $headers);

    try {
      $response = $this->client->send($request, $options);
    }
    Catch( \Exception $e ){
      //Logger::addMessage(array('message' => 'Response error: <pre>' . print_r($e, TRUE) . '</pre>'));

      if ($e instanceof ClientException) {
        $mollom_response = array(
          'code' => $e->getCode(),
          'message' => $e->getResponse()->getReasonPhrase(),
          'headers' => $e->getResponse()->getHeaders(),
          'body' => $e->getResponse()->getBody(),
        );
      }
      else {
        Logger::addMessage(array(
            'message' => 'failed to connect. Message @message',
            'arguments' => array('@message' => $e->getMessage())
          ), RfcLogLevel::ERROR);
        return (object) array(
          'code' => '0',
          'message' => $e->getMessage(),
          'headers' => array(),
          'body' => '',
        );
      }
    }

    if (empty($mollom_response)) {
      $mollom_response = array(
        'code' => $response->getStatusCode(),
        'message' => ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) ? $response->getReasonPhrase() : NULL,
        'headers' => $response->getHeaders(),
        'body' => $response->getBody(),
      );
    }
    // Convert headers to expected and consistent format.
    $headers = array();
    foreach ($mollom_response['headers'] as $key => $header) {
      $headers[Unicode::strtolower($key)] = $header[0];
    }
    $mollom_response['headers'] = $headers;
    return (object) $mollom_response;
  }

  /**
   * Retrieves GET/HEAD or POST/PUT parameters of an inbound request.
   *
   * @return array
   *   An array containing either GET/HEAD query string parameters or POST/PUT
   *   post body parameters. Parameter parsing accounts for multiple request
   *   parameters in non-PHP format; e.g., 'foo=one&foo=bar'.
   */
  public static function getServerParameters() {
    $data = parent::getServerParameters();
    if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'HEAD') {
      // Remove $_GET['q'].
      unset($data['q']);
    }
    return $data;
  }

  /**
   * Helper function to prepare a list of available languages.
   *
   * @return array
   *   An array of languages supported by Mollom with keys as the language code
   *   and values as the translated display values.
   */
  public static function getSupportedLanguages() {
    $languages = LanguageManager::getStandardLanguageList();
    $supported = array_flip(self::$LANGUAGES_SUPPORTED);
    $supported = array_combine(array_keys($supported), array_keys($supported));

    // Define those mappings that differ between Drupal codes and Mollom codes.
    $mapped = array(
      'nb' => 'no',
      'zh-hans' => 'zh-cn',
      'zh-hant' => 'zh-tw',
    );
    foreach($mapped as $drupal_key => $mollom_key) {
      if (isset($supported[$mollom_key])) {
        $supported[$drupal_key] = $mollom_key;
        unset($supported[$mollom_key]);
      }
    }

    $options = array();
    $installed_languages = array();

    // This does assume that all Mollom supported languages are in the predefined
    // Drupal list.
    foreach ($languages as $langcode => $language) {
      $found = FALSE;
      $simplified_code = strtok($langcode, '-');
      if (isset($supported[$simplified_code]) && !isset($options[$simplified_code])) {
        $options[$supported[$simplified_code]] = t($language[0]);
        $found = TRUE;
      }
      else if (isset($supported[$langcode]) && !isset($options[$langcode])) {
        $options[$supported[$langcode]] = t($language[0]);
        $found = TRUE;
      }
    }
    // Sort by translated option labels.
    asort($options);
    // UX: Sort installed languages first.
    // @todo array_intersect_key($options, $installed_languages) + $options;
    return $options;
  }

  /**
   * Implements flattenExpectedLanguages().
   */
  public static function flattenExpectedLanguages(array $languages) {
    return implode(',', $languages);
  }
}
