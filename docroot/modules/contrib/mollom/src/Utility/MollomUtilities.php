<?php

namespace Drupal\mollom\Utility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\mollom\Entity\FormInterface;

class MollomUtilities {

  /**
   * Recursive helper function to flatten nested form values.
   *
   * Takes a potentially nested array and returns all non-empty string values in
   * nested keys as new indexed array.
   */
  public static function flattenFormValue($values) {
    $flat_values = [];
    foreach ($values as $value) {
      if (is_array($value)) {
        // Only text fields are supported at this point; their values are in the
        // 'summary' (optional) and 'value' keys.
        if (isset($value['value'])) {
          if (isset($value['summary']) && $value['summary'] !== '') {
            $flat_values[] = $value['summary'];
          }
          if ($value['value'] !== '') {
            $flat_values[] = $value['value'];
          }
        }
        elseif (!empty($value)) {
          $flat_values = array_merge($flat_values, self::flattenFormValue($value));
        }
      }
      elseif (is_string($value) && strlen($value)) {
        $flat_values[] = $value;
      }
    }
    return $flat_values;
  }

  /**
   * Helper function to determine protected forms for an entity.
   *
   * @param $type
   *   The type of entity to check.
   * @param $bundle
   *   An array of bundle names to check.
   *
   * @return array
   *   An array of protected bundles for this entity type.
   */
  public static function _mollom_get_entity_forms_protected($type, $bundles = array()) {
    // Find out if this entity bundle is protected.
    $protected = &drupal_static(__FUNCTION__, array());
    if (empty($bundles)) {
      $info = entity_get_info($type);
      $bundles = array_keys($info['bundles']);
    }
    $protected_bundles = array();
    foreach ($bundles as $bundle) {
      if (!isset($protected[$type][$bundle])) {
        $protected[$type][$bundle] = db_query_range('SELECT 1 FROM {mollom_form} WHERE entity = :entity AND bundle = :bundle', 0, 1, array(
          ':entity' => $type,
          ':bundle' => isset($bundle) ? $bundle : $type,
        ))->fetchField();
      }
      if (!empty($protected[$type][$bundle])) {
        $protected_bundles[] = $bundle;
      }
    }
    return $protected_bundles;
  }

  /**
   * Returns the (last known) status of the configured Mollom API keys.
   *
   * @param bool $force
   *   (optional) Boolean whether to ignore the cached state and re-check.
   *   Defaults to FALSE.
   * @param bool $update
   *   (optional) Whether to update Mollom with locally stored configuration.
   *   Defaults to FALSE.
   *
   * @return array
   *   An associative array describing the current status of the module:
   *   - isConfigured: Boolean whether Mollom API keys have been configured.
   *   - isVerified: Boolean whether Mollom API keys have been verified.
   *   - response: The response error code of the API verification request.
   *   - ...: The full site resource, as returned by the Mollom API.
   *
   * @see mollom_requirements()
   */
  public static function getAPIKeyStatus($force = FALSE, $update = FALSE) {
    $testing_mode = (int) \Drupal::config('mollom.settings')
      ->get('test_mode.enabled');
    /*
    $static_cache = &drupal_static(__FUNCTION__, array());
    $status = &$static_cache[$testing_mode];

    $drupal_cache = \Drupal::cache();
    $cid = 'mollom_status:' . $testing_mode;
    $expire_valid = 86400; // once per day
    $expire_invalid = 3600; // once per hour

    // Look for cached status.
    if (!$force) {
      if (isset($status)) {
        return $status;
      }
      else if ($cache = $drupal_cache->get($cid)) {
        return $cache->data;
      }
    }*/

    // Re-check configuration status.
    /** @var \Drupal\mollom\API\DrupalClient $mollom */
    $mollom = \Drupal::service('mollom.client');
    $status = array(
      'isConfigured' => FALSE,
      'isVerified' => FALSE,
      'isTesting' => (bool) $testing_mode,
      'response' => NULL,
      'publicKey' => $mollom->loadConfiguration('publicKey'),
      'privateKey' => $mollom->loadConfiguration('privateKey'),
      'expectedLanguages' => $mollom->loadConfiguration('expectedLanguages'),
    );
    $status['isConfigured'] = (!empty($status['publicKey']) && !empty($status['privateKey']));
    $status['expectedLanguages'] = is_array($status['expectedLanguages']) ? array_values($status['expectedLanguages']) : [];

    if ($testing_mode || $status['isConfigured']) {
      $old_status = $status;
      $data = array();
      if ($update) {
        // Ensure to use the most current API keys (might have been changed).
        $mollom->publicKey = $status['publicKey'];
        $mollom->privateKey = $status['privateKey'];

        $data += array(
          'expectedLanguages' => $status['expectedLanguages'],
        );
      }
      $data += $mollom->getClientInformation();
      $response = $mollom->updateSite($data);

      if (is_array($response) && $mollom->lastResponseCode === TRUE) {
        $status = array_merge($status, $response);
        $status['isVerified'] = TRUE;
        Logger::addMessage(array(
          'message' => 'API keys are valid.',
        ), RfcLogLevel::INFO);

        // Unless we just updated, update local configuration with remote.
        if ($update) {
          if ($old_status['expectedLanguages'] != $status['expectedLanguages']) {
            $mollom->saveConfiguration('expectedLanguages', is_array($status['expectedLanguages']) ? $status['expectedLanguages'] : explode(',', $status['expectedLanguages']));
          }
        }
      }
      elseif ($response === $mollom::AUTH_ERROR) {
        $status['response'] = $response;
        Logger::addMessage(array(
          'message' => 'Invalid API keys.',
        ), RfcLogLevel::ERROR);
      }
      elseif ($response === $mollom::REQUEST_ERROR) {
        $status['response'] = $response;
        Logger::addMessage(array(
          'message' => 'Invalid client configuration.',
        ), RfcLogLevel::ERROR);
      }
      else {
        $status['response'] = $response;
        // A NETWORK_ERROR and other possible responses may be caused by the
        // client-side environment, but also by Mollom service downtimes. Try to
        // recover as soon as possible.
        $expire_invalid = 60 * 5;
        Logger::addMessage(array(
          'message' => 'API keys could not be verified.',
        ), RfcLogLevel::ERROR);
      }
    }
    //$drupal_cache->set($cid, $status, $status['isVerified'] === TRUE ? $expire_valid : $expire_invalid);
    return $status;
  }

  /**
   * Gets the status of Mollom's API key configuration and also displays a
   * warning message if the Mollom API keys are not configured.
   *
   * To be used within the Mollom administration pages only.
   *
   * @param bool $force
   *   (optional) Boolean whether to ignore the cached state and re-check.
   *   Defaults to FALSE.
   * @param bool $update
   *   (optional) Whether to update Mollom with locally stored configuration.
   *   Defaults to FALSE.
   *
   * @return array
   *   An associative array describing the current status of the module:
   *   - isConfigured: Boolean whether Mollom API keys have been configured.
   *   - isVerified: Boolean whether Mollom API keys have been verified.
   *   - response: The response error code of the API verification request.
   *   - ...: The full site resource, as returned by the Mollom API.
   *
   * @see Mollom::getAPIKeyStatus().
   */
  public static function getAdminAPIKeyStatus($force = FALSE, $update = FALSE) {
    $status = MollomUtilities::getAPIKeyStatus($force, $update);
    if (empty($_POST) && !$status['isVerified']) {
      // Fetch and display requirements error message, without re-checking.
      module_load_install('mollom');
      $requirements = mollom_requirements('runtime', FALSE);
      if (isset($requirements['mollom']['description'])) {
        drupal_set_message($requirements['mollom']['description'], 'error');
      }
    }
    return $status;
  }


  /**
   * Outputs a warning message about enabled testing mode (once).
   */
  public static function displayMollomTestModeWarning() {
    // drupal_set_message() starts a session and disables page caching, which
    // breaks cache-related tests. Thus, tests set the verbose variable to TRUE.
    if (\Drupal::state()->get('mollom.omit_warning') ?: FALSE) {
      return;
    }

    if (\Drupal::config('mollom.settings')->get('test_mode.enabled') && empty($_POST)) {
      $admin_message = '';
      if (\Drupal::currentUser()
          ->hasPermission('administer mollom') && \Drupal::routeMatch()
          ->getRouteName() != 'mollom.settings'
      ) {
        $admin_message = t('Visit the <a href="@settings-url">Mollom settings page</a> to disable it.', array(
          '@settings-url' => Url::fromRoute('mollom.settings')->toString(),
        ));
      }
      $message = t('Mollom testing mode is still enabled. @admin-message', array(
        '@admin-message' => $admin_message,
      ));
      drupal_set_message($message, 'warning', FALSE);
    }
  }

  /**
   * Helper function to log and optionally output an error message when Mollom servers are unavailable.
   */
  public static function handleFallback(FormStateInterface $form_state = NULL, $element_name = '') {
    $fallback = \Drupal::config('mollom.settings')->get('fallback');
    if ($fallback == FormInterface::MOLLOM_FALLBACK_BLOCK) {
      $block_message = t("The spam filter installed on this site is currently unavailable. Per site policy, we are unable to accept new submissions until that problem is resolved. Please try resubmitting the form in a couple of minutes.");
      drupal_set_message($block_message, 'error');
      if (!empty($form_state) && !empty($element_name)) {
        $form_state->setErrorByName($element_name, $block_message);
      }
    }
    return TRUE;
  }

  /**
   * Formats a message for end-users to report false-positives.
   *
   * @param array $form_state
   *   The current state of the form.
   * @param array $data
   *   The latest Mollom session data pertaining to the form submission attempt.
   *
   * @return string
   *   A message string containing a specially crafted link to Mollom's
   *   false-positive report form, supplying these parameters:
   *   - public_key: The public API key of this site.
   *   - url: The current, absolute URL of the form.
   *   At least one or both of:
   *   - contentId: The content ID of the Mollom session.
   *   - captchaId: The CAPTCHA ID of the Mollom session.
   *   If available, to speed up and simplify the false-positive report form:
   *   - authorName: The author name, if supplied.
   *   - authorMail: The author's e-mail address, if supplied.
   */
  public static function formatFalsePositiveMessage(FormStateInterface $form_state, $data) {
    $mollom = \Drupal::service('mollom.client');
    $report_url = 'https://mollom.com/false-positive';
    $params = array(
      'public_key' => $mollom->loadConfiguration('publicKey'),
    );
    $params += array_intersect_key($form_state->getValue('mollom'), array_flip(array(
      'contentId',
      'captchaId'
    )));
    $params += array_intersect_key($data, array_flip(array(
      'authorName',
      'authorMail'
    )));
    $params['url'] = $GLOBALS['base_root'] . \Drupal::request()
        ->getRequestUri();
    $report_url = Url::fromUri($report_url, array('query' => $params))
      ->toUriString();
    return t('If you feel this is in error, please <a href="@report-url" class="mollom-target">report that you are blocked</a>.', array(
      '@report-url' => $report_url,
    ));
  }
}
