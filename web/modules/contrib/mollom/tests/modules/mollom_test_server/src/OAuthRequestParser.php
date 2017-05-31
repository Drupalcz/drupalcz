<?php

namespace Drupal\mollom_test_server;


use Drupal\Core\Url;
use Drupal\mollom\API\DrupalClient;

class OAuthRequestParser {

  /**
   * Returns whether the OAuth request signature is valid
   *
   * @param $sites array
   *   An array of valid sites for this client keyed by public key.
   * @return bool
   *   TRUE if a valid signature, FALSE if invalid.
   */
  public function validateOAuth($sites = array()) {
    $data = self::getRestParameters();
    $header = self::getRestOAuthHeader();

    // Validate the timestamp.
    $client_time = $header['oauth_timestamp'];
    $time = REQUEST_TIME;
    $offset = abs($time - $client_time);
    if ($offset > DrupalClient::TIME_OFFSET_MAX) {
      return FALSE;
    }

    $sent_signature = $header['oauth_signature'];
    unset($header['oauth_signature']);

    $url = Url::fromRoute('<current>', array(), array('absolute' => TRUE));
    $base_string = implode('&', array(
      $_SERVER['REQUEST_METHOD'],
      rawurlencode($url->toString()),
      rawurlencode(DrupalClient::httpBuildQuery($data + $header)),
    ));

    if (!isset($sites[$header['oauth_consumer_key']]['privateKey'])) {
      return FALSE;
    }
    $privateKey = $sites[$header['oauth_consumer_key']]['privateKey'];
    $key = rawurlencode($privateKey) . '&' . '';

    $signature = rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, TRUE)));

    return $signature === $sent_signature;
  }

  /**
   * Returns HTTP request query parameters for the current request.
   *
   * @see DrupalClient::getServerParameters()
   * @return array
   *   The parameters passed to the request
   */
  public function getRestParameters() {
    $data = &drupal_static(__FUNCTION__);

    if (isset($data)) {
      return $data;
    }
    $data = DrupalClient::getServerParameters();
    return $data;
  }

  /**
   * Returns the parsed HTTP Authorization request header as an array.
   *
   * @see DrupalClient::getServerAuthentication().
   * @return array
   *   The authentication header
   */
  public function getRestOAuthHeader() {
    $header = &drupal_static(__FUNCTION__);

    if (isset($header)) {
      return $header;
    }
    $header = DrupalClient::getServerAuthentication();
    return $header;
  }
}
