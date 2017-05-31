<?php

namespace Drupal\mollom_test_server;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mollom\API\DrupalClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;

/**
 * Default controller for the mollom test server.
 */
class ServerController extends ControllerBase {

  const KEY_SITES = 'mollom_test_server_sites';
  const KEY_BLACKLIST = 'mollom_test_server_blacklist';
  const KEY_CAPTCHA = 'mollom_test_server_captcha';
  const KEY_CONTENT = 'mollom_test_server_content';
  const KEY_CONTENT_CAPTCHA = 'mollom_test_server_content_captcha';
  const KEY_FEEDBACK = 'mollom_test_server_feedback';

  /**
   * Handles parsing authentication and header parameters.
   * @var OAuthRequestParser
   * \Drupal\mollom_test_server\OAuthRequestParser
   */
  protected $parser;

  /**
   * Providers XML serialization.
   * @var SerializerInterface
   * \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Constructs a new BlockController instance.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(OAuthRequestParser $parser, Serializer $serializer) {
    $this->parser = $parser;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mollom_test_server.request_parser'),
      $container->get('serializer')
    );
  }

  /**
   * Get a listing of all sites.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getSites() {
    $sites = $this->state()->get(self::KEY_SITES) ?: array();
    $response = array(
      'list' => array_values($sites),
      'listCount' => count($sites),
      'listOffset' => 0,
      'listTotal' => count($sites),
    );
    return $this->getSuccessResponse($response);
  }

  /**
   * Get the data for a single site.
   * @param $publicKey
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getSite($publicKey) {
    $sites = $this->state()->get(self::KEY_SITES) ?: array();

    // Validate authentication.
    if (!$this->parser->validateOAuth($sites)) {
      return $this->getErrorResponse(DrupalClient::AUTH_ERROR);
    }
    // Check whether publicKey exists.
    if (!isset($sites[$publicKey])) {
      return $this->getErrorResponse(Response::HTTP_NOT_FOUND);
    }
    $response = $sites[$publicKey];
    return $this->getSuccessResponse(array('site' => $response));
  }

  /**
   * Create a new site.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function createSite() {
    $data = $this->parser->getRestParameters();
    $sites = $this->state()->get(self::KEY_SITES) ?: array();

    $data['publicKey'] = $publicKey = md5(rand() . REQUEST_TIME);
    $data['privateKey'] = $privateKey = md5(rand() . REQUEST_TIME);
    // Apply default values.
    $data += array(
      'url' => '',
      'email' => '',
      'expectedLanguages' => array(),
      'subscriptionType' => '',
      // Client version info is not defined by default.
    );
    $sites[$publicKey] = $data;
    $this->state()->set(self::KEY_SITES, $sites);
    $response = $data;
    return $this->getSuccessResponse(array('site' => $response));
  }

  /**
   * Update an existing site
   * @param $publicKey
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function updateSite($publicKey) {
    $data = $this->parser->getRestParameters();
    $sites = $this->state()->get(self::KEY_SITES) ?: array();

    // Validate authentication.
    if (!$this->parser->validateOAuth($sites)) {
      return $this->getErrorResponse(DrupalClient::AUTH_ERROR);
    }
    // Check whether publicKey exists.
    if (!isset($sites[$publicKey])) {
      return $this->getErrorResponse(Response::HTTP_NOT_FOUND);
    }

    $sites[$publicKey] = $data + $sites[$publicKey];
    $this->state()->set(self::KEY_SITES, $sites);
    $response = $sites[$publicKey];
    return $this->getSuccessResponse(array('site' => $response));
  }

  /**
   * Delete an existing site.
   * @param $publicKey
   * @return bool|\Symfony\Component\HttpFoundation\Response
   */
  public function deleteSite($publicKey) {
    $sites = $this->state()->get(self::KEY_SITES) ?: array();

    // Validate authentication.
    if (!$this->parser->validateOAuth($sites)) {
      return $this->getErrorResponse(DrupalClient::AUTH_ERROR);
    }
    // Check whether publicKey exists.
    if (!isset($sites[$publicKey])) {
      return $this->getErrorResponse(Response::HTTP_NOT_FOUND);
    }

    unset($sites[$publicKey]);
    $this->state()->set(self::KEY_SITES, $sites);
    return $this->getSuccessResponse();
  }

  /**
   * REST callback for mollom.checkContent to perform textual analysis.
   *
   * Note: This is limited to POST methods in the routing configuration.
   *
   * @param string $contentId
   *   The ID of the content to check if content has already been checked.
   * @return \Symfony\Component\HttpFoundation\Response
   *   A valid HTTP response.
   */
  public function content($contentId = NULL) {
    $data = $this->parser->getRestParameters();
    // Content ID in request parameters must match the one in path.
    if (isset($data['id']) && $data['id'] != $contentId) {
      return $this->getErrorResponse();
    }
    if (isset($contentId)) {
      $data['id'] = $contentId;
    }

    // Default POST: Create or update content and check it.
    return $this->getSuccessResponse(array('content' => $this->checkContent($data)));
  }

  /**
   * REST callback to for CAPTCHAs.
   *
   * @param string $captchaId
   *   The id of the captcha if checking an existing captcha.
   * @return \Symfony\Component\HttpFoundation\Response
   *   A valid HTTP response.
   */
  public function captcha($captchaId = NULL) {
    $data = $this->parser->getRestParameters();
    // CAPTCHA ID in request parameters must match the one in path.
    if (isset($data['id']) && $data['id'] != $captchaId) {
      return $this->getErrorResponse();
    }
    // Verify CAPTCHA.
    if (isset($captchaId)) {
      $data['id'] = $captchaId;
      $response = $this->checkCaptcha($data);
      if (!is_array($response)) {
        return $this->getErrorResponse($response);
      }
      return $this->getSuccessResponse(array('captcha' => $response));
    }
    // Create a new CAPTCHA resource.
    return $this->getSuccessResponse(array('captcha' => $this->getCaptcha($data)));
  }

  /**
   * REST callback for Blacklist API.
   *
   * @param string $public_key
   *   The public key of a site.
   * @param string $entryId
   *   The id of a blacklist entry
   * @param bool $delete
   *   TRUE to delete the blacklist entry.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A valid HTTP response.
   *
   * @todo Abstract actual functionality like other REST handlers.
   */
  public function blacklist($public_key, $entryId = NULL, $delete = FALSE) {
    if (empty($public_key)) {
      return FALSE;
    }
    $data = $this->parser->getRestParameters();

    // Prepare text value.
    if (isset($data['value'])) {
      $data['value'] = Unicode::strtolower(trim($data['value']));
    }

    $key = self::KEY_BLACKLIST . '_' . $public_key;
    $entries = $this->state()->get($key, array());

    if (\Drupal::request()->getMethod() == 'GET') {
      // List blacklist entries.
      if (empty($entryId)) {
        $response = array();
        // Remove deleted entries (== FALSE).
        $entries = array_filter($entries);
        $response['list'] = $entries;
        // @todo Not required yet.
        $response['listCount'] = count($entries);
        $response['listOffset'] = 0;
        $response['listTotal'] = count($entries);
        return $this->getSuccessResponse($response);
      }
      // Read a single entry.
      else {
        // Check whether the entry exists and was not deleted.
        if (!empty($entries[$entryId])) {
          return $this->getSuccessResponse(array('entry' => $entries[$entryId]));
        }
        else {
          return $this->getErrorResponse(Response::HTTP_NOT_FOUND);
        }
      }
    }
    else {
      // Update an existing entry.
      if (isset($entryId)) {
        // Entry ID must match.
        if (isset($data['id']) && $data['id'] != $entryId) {
          return $this->getErrorResponse();
        }
        // Check that the entry was not deleted.
        if (empty($entries[$entryId])) {
          return $this->getErrorResponse(Response::HTTP_NOT_FOUND);
        }
        // Entry ID cannot be updated.
        unset($data['id']);
        $entries[$entryId] = $data;
        $this->state()->set($key, $entries);
        $response = $data;
        $response['id'] = $entryId;
        return $this->getSuccessResponse(array('entry' => $response));
      }
      // Create a new entry.
      elseif (!$delete) {
        $entryId = max(array_keys($entries)) + 1;
        $data['id'] = $entryId;
        $entries[$entryId] = $data;
        $this->state()->set($key, $entries);

        $response = $data;
        return $this->getSuccessResponse(array('entry' => $response));
      }
      // Delete an existing entry.
      else {
        // Check that the entry was not deleted already.
        if (!empty($entries[$entryId])) {
          $entries[$entryId] = FALSE;
          $this->state()->set($key, $entries);
          return $this->getSuccessResponse();
        }
        else {
          return $this->getErrorResponse(Response::HTTP_NOT_FOUND);
        }
      }
    }
  }

  /**
   * REST callback for mollom.sendFeedback to send feedback for a moderated post.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A valid HTTP response.
   */
  public function feedback() {
    $data = $this->parser->getRestParameters();
    // A resource ID is required.
    if (empty($data['contentId']) && empty($data['captchaId'])) {
      return $this->getErrorResponse(Response::HTTP_BAD_REQUEST);
    }

    // The feedback is valid if the supplied reason is one of the supported
    // strings. Otherwise, it's a bad request.
    $storage = $this->state()->get(self::KEY_FEEDBACK, array());
    $storage[] = $data;
    $this->state()->set(self::KEY_FEEDBACK, $storage);

    // Default value assumed in the API for feedback type is "moderate".
    if (empty($data['type'])) {
      $data['type'] = 'moderate';
    }

    $reason_result = in_array($data['reason'], array('spam', 'profanity', 'quality', 'unwanted', 'approve', 'delete'));
    $feedback_result = in_array($data['type'], array('flag', 'moderate'));
    return $reason_result && $feedback_result ? $this->getSuccessResponse() : $this->getErrorResponse();
  }

  /**
   * API callback for mollom.checkContent to perform textual analysis.
   *
   * @todo Add support for 'redirect' and 'refresh' values.
   */
  protected function checkContent($data) {
    $response = array();

    // If only a single value for checks is passed, it is a string.
    if (isset($data['checks']) && is_string($data['checks'])) {
      $data['checks'] = array($data['checks']);
    }

    $header = $this->parser->getRestOAuthHeader();
    $publicKey = $header['oauth_consumer_key'];

    // Fetch blacklist.
    $blacklist = $this->state()->get(self::KEY_BLACKLIST . '_' . $publicKey, array());

    // Determine content keys to analyze.
    $post_keys = array('postTitle' => 1, 'postBody' => 1);
    $type = FALSE;
    if (isset($data['type']) && in_array($data['type'], array('user'))) {
      $type = $data['type'];
      if ($type == 'user') {
        $post_keys += array('authorName' => 1, 'authorMail' => 1);
      }
    }
    $post = implode('\n', array_intersect_key($data, $post_keys));

    $update = isset($data['stored']);

    // Spam filter: Check post_title and post_body for ham, spam, or unsure.
    if (!$update && (!isset($data['checks']) || in_array('spam', $data['checks']))) {
      $spam = FALSE;
      $ham = FALSE;
      // 'spam' always has precedence.
      if (strpos($post, 'spam') !== FALSE) {
        $spam = TRUE;
      }
      // Otherwise, check for 'ham'.
      elseif (strpos($post, 'ham') !== FALSE) {
        $ham = TRUE;
      }
      // Lastly, take a forced 'unsure' into account.
      elseif (strpos($post, 'unsure') !== FALSE) {
        // Enabled unsure mode.
        if (!isset($data['unsure']) || $data['unsure']) {
          $spam = TRUE;
          $ham = TRUE;
        }
        // Binary mode.
        else {
          $spam = FALSE;
          $ham = TRUE;
        }
      }
      // Check blacklist.
      if ($matches = $this->checkBlacklist($post, $blacklist, 'spam')) {
        $spam = TRUE;
        $ham = FALSE;
        $response['reason'] = 'blacklist';
        $response['blacklistSpam'] = $matches;
      }

      if ($spam && $ham) {
        $response['spamScore'] = 0.5;
        $response['spamClassification'] = 'unsure';
        $qualityScore = 0.5;
      }
      elseif ($spam) {
        $response['spamScore'] = 1.0;
        $response['spamClassification'] = 'spam';
        $qualityScore = 0.0;
      }
      elseif ($ham) {
        $response['spamScore'] = 0.0;
        $response['spamClassification'] = 'ham';
        $qualityScore = 1.0;
      }
      else {
        $response['spamScore'] = 0.5;
        $response['spamClassification'] = 'unsure';
        $qualityScore = NULL;
      }
      // In case a previous spam check was unsure and a CAPTCHA was solved, the
      // result is supposed to be ham - unless the new content is spam.
      if (!empty($data['id']) && $response['spamClassification'] == 'unsure') {
        $content_captchas = $this->state()->get(self::KEY_CONTENT_CAPTCHA, array());
        if (!empty($content_captchas[$data['id']])) {
          $response['spamScore'] = 0.0;
          $response['spamClassification'] = 'ham';
        }
      }
    }

    // Quality filter.
    if (isset($data['checks']) && in_array('quality', $data['checks'])) {
      if (isset($qualityScore)) {
        $response['qualityScore'] = $qualityScore;
      }
      else {
        $response['qualityScore'] = 0;
      }
    }

    // Profanity filter.
    if (isset($data['checks']) && in_array('profanity', $data['checks'])) {
      $profanityScore = 0.0;
      if (strpos($post, 'profanity') !== FALSE) {
        $profanityScore = 1.0;
      }
      // Check blacklist.
      if ($matches = $this->checkBlacklist($post, $blacklist, 'profanity')) {
        $profanityScore = 1.0;
        $response['blacklistProfanity'] = $matches;
      }
      $response['profanityScore'] = $profanityScore;
    }

    // Language detection.
    if (isset($data['checks']) && in_array('language', $data['checks'])) {
      $languages = array();
      if (stripos($post, 'ist seit der Mitte')) {
        $languages[] = array(
          'languageCode' => 'de',
        );
      }
      if (stripos($post, 'it is the most populous city')) {
        $languages[] = array(
          'languageCode' => 'en',
        );
      }
      if (count($languages) == 0) {
        $languages[] = array(
          'languageCode' => 'zxx',
        );
      }
      $score = 1/count($languages);
      foreach($languages as $id => &$langObj) {
        $langObj['languageScore'] = $score;
      }
      if (count($languages) === 1) {
        $response['languages']['language'] = reset($languages);
      } else {
        $response['languages'] = [$languages];
      }
    }
    $storage = $this->state()->get(self::KEY_CONTENT, array());
    $contentId = (!empty($data['id']) ? $data['id'] : md5(mt_rand()));
    if (isset($storage[$contentId])) {
      $storage[$contentId] = array_merge($storage[$contentId], $data);
    }
    else {
      $storage[$contentId] = $data;
    }
    if ($update) {
      $response = array_merge($storage[$contentId], $response);
    }
    $response['id'] = $contentId;
    $this->state()->set(self::KEY_CONTENT, $storage);

    return $response;
  }

  /**
   * Checks a string against blacklisted terms.
   */
  protected function checkBlacklist($string, $blacklist, $reason) {
    $terms = array();
    foreach ($blacklist as $entry) {
      if ($entry['reason'] == $reason) {
        $term = preg_quote($entry['value']);
        if ($entry['match'] == 'exact') {
          $term = '\b' . $term . '\b';
        }
        $terms[] = $term;
      }
    }
    if (!empty($terms)) {
      $terms = '/(' . implode('|', $terms) . ')/';
      preg_match_all($terms, strtolower($string), $matches);
      return $matches[1];
    }
    return array();
  }

  /**
   * API callback for mollom.getImageCaptcha to fetch a CAPTCHA image.
   */
  protected function getCaptcha($data) {
    $captchaId = (!empty($data['id']) ? $data['id'] : md5(mt_rand()));
    $response = array(
      'id' => $captchaId,
    );

    // Return a HTTPS URL if 'ssl' parameter was passed.
    $base_url = $GLOBALS['base_url'];
    if (!empty($data['ssl'])) {
      $base_url = str_replace('http', 'https', $base_url);
    }
    $response['url'] = $base_url . '/' . drupal_get_path('module', 'mollom') . '/images/powered-by-mollom-2.gif?captchaId=' . $captchaId;

    $storage = $this->state()->get(self::KEY_CAPTCHA, array());
    $storage[$captchaId] = $data;
    $this->state()->set(self::KEY_CAPTCHA, $storage);

    return $response;
  }

  /**
   * API callback for mollom.checkCaptcha to validate a CAPTCHA response.
   */
  protected function checkCaptcha($data) {
    $response = array();

    if (isset($data['solution']) && $data['solution'] == 'correct') {
      $response['solved'] = TRUE;
    }
    else {
      $response['solved'] = FALSE;
      $response['reason'] = '';
    }

    $storage = $this->state()->get(self::KEY_CAPTCHA, array());
    $captchaId = $data['id'];
    if (!isset($storage[$captchaId])) {
      return Response::HTTP_NOT_FOUND;
    }
    $storage[$captchaId] = array_merge($storage[$captchaId], $data);
    $response['id'] = $captchaId;
    $this->state()->set(self::KEY_CAPTCHA, $storage);


    if (isset($storage[$captchaId]['contentId'])) {
      $contentId = $storage[$captchaId]['contentId'];
      $content_captchas = $this->state()->get(self::KEY_CONTENT_CAPTCHA, array());
      $content_captchas[$contentId] = $response['solved'];

      $this->state()->set(self::KEY_CONTENT_CAPTCHA, $content_captchas);
    }

    return $response;
  }

  /**
   * Returns a properly formatted error response.
   *
   * @param $status
   *   The HTTP status code for the type of error.  Defaults to 400 if unknown.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The error response.
   */
  protected function getErrorResponse($status = Response::HTTP_BAD_REQUEST) {
    switch ($status) {
      case DrupalClient::AUTH_ERROR:
        $message = 'Unauthorized';
        $code = Response::HTTP_UNAUTHORIZED;
        break;
      case Response::HTTP_NOT_FOUND:
        $message = 'Not found';
        $code = $status;
        break;
      default:
        $message = 'Bad request';
        $code = Response::HTTP_BAD_REQUEST;
        break;
    }
    $content = array(
      'status' => $status,
      'message' => $message,
    );
    return $this->serializeResponseData($content, $code);
  }

  /**
   * Returns a properly formatted success response.
   *
   * @param array $data
   *   The data to return.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The error response for chaining.
   */
  protected function getSuccessResponse($data = array()) {
    $content = array(
      'status' => 200,
    );
    $content += $data;
    return $this->serializeResponseData($content);
  }

  /**
   * Serializes the response data in xml format.
   *
   * @param array $data
   *   The data to serialize
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response for chaining.
   */
  protected function serializeResponseData($data, $code = Response::HTTP_OK) {
    $output = $this->serializer->serialize($data, 'xml');
    $response = new Response($output, $code);
    $response->headers->set('Content-Type', 'application/xml; charset=utf-8');
    return $response;
  }
}

