<?php

namespace Drupal\mollom\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\mollom\Element\Mollom;
use Drupal\mollom\Storage\ResponseDataStorage;
use Drupal\mollom\Utility\Logger;
use Drupal\mollom\Utility\MollomUtilities;
use Drupal\Core\Form\FormState;
use Drupal\user\Entity\User;

/**
 * Controller with functions for managing protected forms.
 */
class FormController extends ControllerBase {

  const MESSAGE_CAPTCHA = 'To complete this form, please complete the word verification.';

  /**
   * Returns information about a form registered via hook_mollom_form_info().
   *
   * @param $form_id
   *   The form id to return information for.
   * @param $module
   *   The module name $form_id belongs to.
   * @param array $form_list
   *   (optional) The return value of hook_mollom_form_list() of $module, if
   *   already kown. Primarily used by mollom_form_load().
   */
  public static function getProtectedFormDetails($form_id, $module, $form_list = NULL) {
    // Default properties.
    $form_info = array(
      // Base properties.
      'form_id' => $form_id,
      'title' => $form_id,
      'module' => $module,
      'entity' => NULL,
      'bundle' => NULL,
      // Configuration properties.
      'mode' => NULL,
      'checks' => array(),
      'enabled_fields' => array(),
      'strictness' => 'normal',
      'unsure' => 'captcha',
      'discard' => 1,
      'moderation' => 0,
      // Meta information.
      'bypass access' => array(),
      'elements' => array(),
      'mapping' => array(),
      'mail ids' => array(),
      'orphan' => TRUE,
    );

    // Fetch the basic form information from hook_mollom_form_list() first.
    // This makes the integrating module (needlessly) rebuild all of its available
    // forms, but the base properties are absolutely required here, so we can
    // apply the default properties below.
    if (!isset($form_list)) {
      $form_list = \Drupal::moduleHandler()
        ->invoke($module, 'mollom_form_list');
    }
    // If it is not listed, then the form has vanished.
    if (!isset($form_list[$form_id])) {
      return $form_info;
    }
    $module_form_info = \Drupal::moduleHandler()
      ->invoke($module, 'mollom_form_info', array($form_id));
    // If no form info exists, then the form has vanished.
    if (!isset($module_form_info)) {
      return $form_info;
    }
    unset($form_info['orphan']);

    // Any information in hook_mollom_form_info() overrides the list info.
    $form_info = array_merge($form_info, $form_list[$form_id]);
    $form_info = array_merge($form_info, $module_form_info);

    // Allow modules to alter the default form information.
    \Drupal::moduleHandler()->alter('mollom_form_info', $form_info, $form_id);

    return $form_info;
  }

  /**
   * Given an array of values and an array of fields, extract data for use.
   *
   * This function generates the data to send for validation to Mollom by walking
   * through the submitted form values and
   * - copying element values as specified via 'mapping' in hook_mollom_form_info()
   *   into the dedicated data properties
   * - collecting and concatenating all fields that have been selected for textual
   *   analysis into the 'post_body' property
   *
   * The processing accounts for the following possibilities:
   * - A field was selected for textual analysis, but there is no submitted form
   *   value. The value should have been appended to the 'post_body' property, but
   *   will be skipped.
   * - A field is contained in the 'mapping' and there is a submitted form value.
   *   The value will not be appended to the 'post_body', but instead be assigned
   *   to the specified data property.
   * - All fields specified in 'mapping', for which there is a submitted value,
   *   but which were NOT selected for textual analysis, are assigned to the
   *   specified data property. This is usually the case for form elements that
   *   hold system user information.
   *
   * @param $form_state
   *   An associative array containing
   *   - values: The submitted form values.
   *   - buttons: A list of button form elements. See form_state_values_clean().
   * @param $fields
   *   A list of strings representing form elements to extract. Nested fields are
   *   in the form of 'parent][child'.
   * @param $mapping
   *   An associative array of form elements to map to Mollom's dedicated data
   *   properties. See hook_mollom_form_info() for details.
   *
   * @see hook_mollom_form_info()
   */
  public static function extractMollomValues(FormState $form_state, $fields, $mapping) {
    $user = \Drupal::currentUser();

    // All elements specified in $mapping must be excluded from $fields, as they
    // are used for dedicated $data properties instead. To reduce the parsing code
    // size, we are turning a given $mapping of e.g.
    //   array('post_title' => 'title_form_element')
    // into
    //   array('title_form_element' => 'post_title')
    // and we reset $mapping afterwards.
    // When iterating over the $fields, this allows us to quickly test whether the
    // current field should be excluded, and if it should, we directly get the
    // mapped property name to rebuild $mapping with the field values.
    $exclude_fields = array();
    if (!empty($mapping)) {
      $exclude_fields = array_flip($mapping);
    }
    $mapping = array();

    // Process all fields that have been selected for text analysis.
    $post_body = array();
    foreach ($fields as $field) {
      // Nested elements use a key of 'parent][child', so we need to recurse.
      $parents = explode('][', $field);
      $value = $form_state->getValue($parents);
      // If this field was contained in $mapping and should be excluded, add it to
      // $mapping with the actual form element value, and continue to the next
      // field. Also unset this field from $exclude_fields, so we can process the
      // remaining mappings below.
      if (isset($exclude_fields[$field])) {
        if (is_array($value)) {
          $value = implode(' ', MollomUtilities::flattenFormValue($value));
        }
        $mapping[$exclude_fields[$field]] = $value;
        unset($exclude_fields[$field]);
        continue;
      }
      // Only add form element values that are not empty.
      if (isset($value)) {
        // UTF-8 validation happens later.
        if (is_string($value) && strlen($value)) {
          $post_body[$field] = $value;
        }
        // Recurse into nested values (e.g. multiple value fields).
        elseif (is_array($value) && !empty($value)) {
          // Ensure we have a flat, indexed array to implode(); form values of
          // field_attach_form() use several subkeys.
          $value = MollomUtilities::flattenFormValue($value);
          $post_body = array_merge($post_body, $value);
        }
      }
    }
    $post_body = implode("\n", $post_body);

    // Try to assign any further form values by processing the remaining mappings,
    // which have been turned into $exclude_fields above. All fields that were
    // already used for 'post_body' no longer exist in $exclude_fields.
    foreach ($exclude_fields as $field => $property) {
      // If the postTitle field was not included in the enabled fields, then don't
      // set it's mapping here.
      if ($property === 'post_title' && !in_array($field, $fields)) {
        continue;
      }
      // Nested elements use a key of 'parent][child', so we need to recurse.
      $parents = explode('][', $field);
      $value = $form_state->getValue($parents);
      if (isset($value)) {
        if (is_array($value)) {
          $value = MollomUtilities::flattenFormValue($value);
          $value = implode(' ', $value);
        }
        $mapping[$property] = $value;
      }
    }

    // Build the data structure expected by the Mollom API.
    $data = array();
    // Post id; not sent to Mollom.
    // @see submitForm()
    if (!empty($mapping['post_id'])) {
      $data['postId'] = $mapping['post_id'];
    }
    // Post title.
    if (!empty($mapping['post_title'])) {
      $data['postTitle'] = $mapping['post_title'];
    }
    // Post body.
    if (!empty($post_body)) {
      $data['postBody'] = $post_body;
    }

    // Author ID.
    // If a non-anonymous user ID was mapped via form values, use that.
    if (!empty($mapping['author_id'])) {
      $data['authorId'] = $mapping['author_id'];
    }
    // Otherwise, the currently logged-in user is the author.
    elseif (!empty($user->id())) {
      $data['authorId'] = $user->id();
    }

    // Load the user account of the author, if any, for the following author*
    // property assignments.
    $account = FALSE;
    if (isset($data['authorId'])) {
      /** @var \Drupal\user\Entity\User $account */
      $account = User::load($data['authorId']);
      $author_username = $account->getUsername();
      $author_email = $account->getEmail();

      // Author creation date.
      $data['authorCreated'] = $account->getCreatedTime();

      // In case a post of a registered user is edited and a form value mapping
      // exists for author_id, but no form value mapping exists for author_name,
      // use the name of the user account associated with author_id.
      // $account may be the same as the currently logged-in $user at this point.
      if (!empty($author_username)) {
        $data['authorName'] = $author_username;
      }

      if (!empty($author_email)) {
        $data['authorMail'] = $author_email;
      }
    }

    // Author name.
    // A form value mapping always has precedence.
    if (!empty($mapping['author_name'])) {
      $data['authorName'] = $mapping['author_name'];
    }

    // Author e-mail.
    if (!empty($mapping['author_mail'])) {
      $data['authorMail'] = $mapping['author_mail'];
    }

    // Author homepage.
    if (!empty($mapping['author_url'])) {
      $data['authorUrl'] = $mapping['author_url'];
    }

    // Author OpenID.
    if (!empty($mapping['author_openid'])) {
      $data['authorOpenid'] = $mapping['author_openid'];
    }

    // Author IP.
    $data['authorIp'] = \Drupal::request()->getClientIp();

    $mollom_form = $form_state->getValue('mollom');

    // Honeypot.
    // For the Mollom backend, it only matters whether 'honeypot' is non-empty.
    // The submitted value is only taken over to allow site administrators to
    // see the actual honeypot value in watchdog log entries.
    if (isset($mollom_form['homepage']) && $mollom_form['homepage'] !== '') {
      $data['honeypot'] = $mollom_form['homepage'];
    }

    // Add the contextCreated parameter if a callback exists.
    if (isset($mollom_form['context created callback']) && function_exists($mollom_form['context created callback'])) {
      if (!empty($mapping['context_id'])) {
        $contextCreated = call_user_func($mollom_form['context created callback'], $mapping['context_id']);
        if ($contextCreated !== FALSE) {
          $data['contextCreated'] = $contextCreated;
        }
      }
    }

    // Ensure that all $data values contain valid UTF-8. Invalid UTF-8 would be
    // sanitized into an empty string, so the Mollom backend would not receive
    // any value.
    $invalid_utf8 = FALSE;
    $invalid_xml = FALSE;
    // Include the CAPTCHA solution user input in the UTF-8 validation.
    $solution = isset($mollom_form['captcha']['captcha_input']) ? array('solution' => $mollom_form['captcha']['captcha_input']) : array();
    foreach ($data + $solution as $key => $value) {
      // Check for invalid UTF-8 byte sequences first.
      if (!Unicode::validateUtf8($value)) {
        $invalid_utf8 = TRUE;
        // Replace the bogus string, since $data will be logged as
        // check_plain(var_export($data)), and check_plain() would empty the
        // entire exported variable string otherwise.
        $data[$key] = '- Invalid UTF-8 -';
      }
      // Since values are transmitted over XML-RPC and not merely output as
      // (X)HTML, they have to be valid XML characters.
      // @see http://www.w3.org/TR/2000/REC-xml-20001006#charsets
      // @see http://drupal.org/node/882298
      elseif (preg_match('@[^\x9\xA\xD\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]@u', $value)) {
        $invalid_xml = TRUE;
      }
    }
    if ($invalid_utf8 || $invalid_xml) {
      $form_state->setErrorByName('', t('Your submission contains invalid characters and will not be accepted.'));

      Logger::addMessage([
        'message' => 'Invalid @type in form values',
        'arguments' => ['@type' => $invalid_utf8 ? 'UTF-8' : 'XML characters'],
        'data' => $data
      ]);
      $data = FALSE;
    }

    return $data;
  }

  /**
   * Returns a list of protectable forms registered via hook_mollom_form_info().
   */
  public static function getProtectableForms() {
    $form_list = array();
    foreach (\Drupal::moduleHandler()
               ->getImplementations('mollom_form_list') as $module) {
      $function = $module . '_mollom_form_list';
      $module_forms = $function();
      foreach ($module_forms as $form_id => $info) {
        $form_list[$form_id] = $info;
        $form_list[$form_id] += array(
          'form_id' => $form_id,
          'module' => $module,
        );
      }
    }

    // Allow modules to alter the form list.
    \Drupal::moduleHandler()->alter('mollom_form_list', $form_list);

    return $form_list;
  }

  /**
   * Returns a cached mapping of protected and delete confirmation form ids.
   *
   * @param $reset
   *   (optional) Boolean whether to reset the static cache, flush the database
   *   cache, and return nothing (TRUE). Defaults to FALSE.
   *
   * @return
   *   An associative array containing:
   *   - protected: An associative array whose keys are protected form IDs and
   *     whose values are the corresponding module names the form belongs to.
   *   - delete: An associative array whose keys are 'delete form' ids and whose
   *     values are protected form ids; e.g.
   * @code
   *     array(
   *       'node_delete_confirm' => 'article_node_form',
   *     )
   * @endcode
   *     A single delete confirmation form id can map to multiple registered
   *     $form_ids, but only the first is taken into account. As in above example,
   *     we assume that all 'TYPE_node_form' definitions belong to the same entity
   *     and therefore have an identical 'post_id' mapping.
   */
  public static function getProtectedForms($reset = FALSE) {
    $forms = &drupal_static(__FUNCTION__);

    if ($reset) {
      unset($forms);
      return true;
    }

    if (isset($forms)) {
      return $forms;
    }

    // Get all forms that are protected
    $protected_forms = \Drupal\mollom\Entity\Form::loadMultiple();
    foreach ($protected_forms as $form_id => $info) {
      $forms['protected'][$form_id] = $info->get('module');
    }

    // Build a list of delete confirmation forms of entities integrating with
    // Mollom, so we are able to alter the delete confirmation form to display
    // our feedback options.
    $forms['delete'] = array();
    foreach (self::getProtectableForms() as $form_id => $info) {
      if (!isset($info['delete form']) || !isset($info['entity'])) {
        continue;
      }
      // We expect that the same delete confirmation form uses the same form
      // element mapping, so multiple 'delete form' definitions are only processed
      // once. Additionally, we only care for protected forms.
      if (!isset($forms['delete'][$info['delete form']]) && isset($forms['protected'][$form_id])) {
        // A delete confirmation form integration requires a 'post_id' mapping.
        $form_info = self::getProtectedFormDetails($form_id, $info['module']);
        if (isset($form_info['mapping']['post_id'])) {
          $forms['delete'][$info['delete form']] = $form_id;
        }
      }
    }
    return $forms;
  }

  /**
   * Helper function to add field form element mappings for fieldable entities.
   *
   * May be used by hook_mollom_form_info() implementations to automatically
   * populate the 'elements' definition with attached text fields on the entity
   * type's bundle.
   *
   * @param array $form_info
   *   The basic information about the registered form. Taken by reference.
   * @param string $entity_type
   *   The entity type; e.g., 'node'.
   * @param string $bundle
   *   The entity bundle name; e.g., 'article'.
   *
   * @return void
   *   $form_info is taken by reference and enhanced with any attached field
   *   mappings; e.g.:
   * @code
   *     $form_info['elements']['field_name][und][0][value'] = 'Field label';
   * @endcode
   */
  public static function addProtectableFields(&$form_info, $entity_type, $bundle) {
    if (!$entity_info = \Drupal::entityManager()->getDefinition($entity_type)) {
      return;
    }
    $form_info['mapping']['post_id'] = $entity_info->getKeys()['id'];
    $title = isset($form_info['mapping']['post_title']) ? $form_info['mapping']['post_title'] : '';
    $title_parts = explode('][', $title);
    $base_title = reset($title_parts);

    // @var $field_definitions \Drupal\Core\Field\FieldDefinitionInterface[]
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
    /* @var $field \Drupal\Core\Field\FieldDefinitionInterface */
    foreach ($field_definitions as $field_name => $field) {
      if ($field_name !== $base_title &&
        !$field->isReadOnly() &&
        !$field->isComputed() &&
        in_array($field->getType(), [
        'string',
        'email',
        'uri',
        'label',
        'plural_label',
        'mail',
        'string_long',
        'text_with_summary',
      ])) {
        $form_info['elements'][$field_name] = $field->getLabel();
      }
    }
  }

  /**
   * Form validation handler for Mollom's CAPTCHA form element.
   *
   * Validates whether a CAPTCHA was solved correctly. A form may contain a
   * CAPTCHA, if it was configured to be protected by a CAPTCHA only, or when the
   * text analysis result is "unsure".
   */
  public static function validateCaptcha(&$form, FormState $form_state) {
    if (!static::shouldValidate($form, $form_state)) {
      return;
    }

    /** @var \Drupal\mollom\Entity\Form $mollom_form */
    $mollom_form = $form_state->getValue('mollom');

    if ($mollom_form['require_analysis']) {
      // For text analysis, only validate the CAPTCHA if there is an ID. If the ID
      // is maliciously removed from the form values, text analysis will punish
      // the author's reputation and present a new CAPTCHA to solve.
      if (empty($mollom_form['captchaId'])) {
        return;
      }
    }
    else {
      // Otherwise, this form is protected with a CAPTCHA only, unless disabled
      // by another module or Mollom was unavailable.
      if (!$mollom_form['require_captcha']) {
        return;
      }
      if ($mollom_form['captchaId'] === 'invalid') {
        MollomUtilities::handleFallback($form_state, 'mollom][captcha');
        return;
      }
      // If there is no CAPTCHA ID yet, retrieve one and throw an error.
      if (empty($mollom_form['captchaId'])) {
        $form_state->setErrorByName('mollom][captcha', self::MESSAGE_CAPTCHA);
        return;
      }
    }

    // Inform text analysis validation that a CAPTCHA was validated, so the
    // appropriate error message can be output.
    $form_state->set('mollom_had_captcha', TRUE);

    // captcha_response_id may only ever be set by this
    // validation handler and must not be changed elsewhere.
    // This only becomes filled for CAPTCHA-only protected forms. For text
    // analysis, the primary 'require_captcha' condition will not be TRUE
    // unless needed in the first place.
    if (!empty($form_state->getValue(['mollom', 'captcha_response_id']))) {
      $form['mollom']['captcha']['#access'] = FALSE;
      $form['mollom']['captcha']['#solved'] = TRUE;
      return;
    }

    // Check the CAPTCHA result.
    // Next to the Mollom session id and captcha result, the Mollom back-end also
    // takes into account the author's IP and local user id (if registered). Any
    // other values are ignored.
    $all_data = self::extractMollomValues($form_state->cleanValues(), $mollom_form['enabled_fields'], $mollom_form['mapping']);
    // Cancel processing upon invalid UTF-8 data.
    if ($all_data === FALSE) {
      return;
    }
    $data = array(
      'id' => $mollom_form['captchaId'],
      'solution' => $mollom_form['captcha']['captcha_input'],
      'authorIp' => $all_data['authorIp'],
    );
    if (isset($all_data['authorId'])) {
      $data['authorId'] = $all_data['authorId'];
    }
    if (isset($all_data['authorCreated'])) {
      $data['authorCreated'] = $all_data['authorCreated'];
    }
    if (isset($all_data['honeypot'])) {
      $data['honeypot'] = $all_data['honeypot'];
    }
    /** @var \Drupal\mollom\API\DrupalClient $mollom */
    $mollom = \Drupal::service('mollom.client');
    $result = $mollom->checkCaptcha($data);
    // Use all available data properties for log messages below.
    $data += $all_data;

    // Handle the result, unless it is FALSE (bogus CAPTCHA ID input).
    if ($result !== FALSE) {
      // Trigger global fallback behavior if there is a unexpected result.
      if (!is_array($result) || !isset($result['id'])) {
        MollomUtilities::handleFallback();
        return;
      }

      // Store the response for #submit handlers.
      $form_state->setValue(array('mollom', 'response', 'captcha'), $result);
      // Set form values accordingly. Do not overwrite the entity ID.
      // @todo Rename 'id' to 'entity_id'.
      $result['captchaId'] = $result['id'];
      unset($result['id']);
      $form_state->setValue('mollom', array_merge($mollom_form, $result));

      // Ensure the latest CAPTCHA ID is output as value.
      $form_state->setValue(array('mollom', 'captchaId'), $result['captchaId']);
    }

    if (!empty($result['solved'])) {
      // For text analysis, remove the CAPTCHA ID from the output if it was
      // solved, so this validation handler does not run again.
      $require_analysis = $form_state->getValue(array('mollom', 'require_analysis'));
      if ($require_analysis) {
        $form['mollom']['captchaId']['#value'] = '';
      }
      $form_state->setValue(['mollom', 'captcha_response_id'], $mollom_form['captchaId']);
      $form['mollom']['captcha_response_id']['#value'] = $mollom_form['captchaId'];
      $form['mollom']['captcha']['#access'] = FALSE;
      $form['mollom']['captcha']['#solved'] = TRUE;

      Logger::addMessage(array(
        'message' => 'Correct CAPTCHA',
      ), RfcLogLevel::INFO);
    }
    else {
      // Text analysis will re-check the content and may trigger a CAPTCHA on its
      // own again (not guaranteed).
      if (!$mollom_form['require_analysis']) {
        $form_state->setErrorByName('mollom][captcha', t('The word verification was not completed correctly. Please complete this new word verification and try again. @fpmessage', array(
          '@fpmessage' => MollomUtilities::formatFalsePositiveMessage($form_state, $data),
        )));
        $form_state->setCached(FALSE);
      }

      Logger::addMessage(array(
        'message' => 'Incorrect CAPTCHA',
      ));
    }
  }

  /**
   * Form validation handler to perform textual analysis on submitted form values.
   */
  public static function validateAnalysis(&$form, FormState $form_state) {
    if (!static::shouldValidate($form, $form_state)) {
      return;
    }
    /** @var \Drupal\mollom\Entity\Form $mollom_form */
    $mollom = $form_state->getValue('mollom');

    if (!$mollom['require_analysis']) {
      return FALSE;
    }

    // Perform textual analysis.
    $all_data = self::extractMollomValues($form_state->cleanValues(), $mollom['enabled_fields'], $mollom['mapping']);
    // Cancel processing upon invalid UTF-8 data.
    if ($all_data === FALSE) {
      return FALSE;
    }
    $data = $all_data;
    // Remove postId property; only used by submitForm().
    if (isset($data['postId'])) {
      unset($data['postId']);
    }
    $contentId = isset($mollom['contentId']) ? $mollom['contentId'] : NULL;
    if (!empty($contentId)) {
      $data['id'] = $contentId;
    }
    if (is_array($mollom['checks'])) {
      $data['checks'] = $mollom['checks'];
    }
    $data['strictness'] = $mollom['strictness'];
    if (isset($mollom['type'])) {
      $data['type'] = $mollom['type'];
    }
    if (in_array('spam', $data['checks']) && $mollom['unsure'] == 'binary') {
      $data['unsure'] = 0;
    }

    // Allow modules to alter data sent.
    \Drupal::moduleHandler()->alter('mollom_content', $data);

    /** @var \Drupal\mollom\API\DrupalClient $mollom */
    $mollom_service = \Drupal::service('mollom.client');
    $result = $mollom_service->checkContent($data);

    // Use all available data properties for log messages below.
    $data += $all_data;

    // Trigger global fallback behavior if there is a unexpected result.
    if (!is_array($result) || !isset($result['id'])) {
      return MollomUtilities::handleFallback();
    }

    // Set form values accordingly. Do not overwrite the entity ID.
    // @todo Rename 'id' to 'entity_id'.
    $result['contentId'] = $result['id'];
    unset($result['id']);

    // Store the response returned by Mollom.
    $form_state->setValue(array('mollom', 'response', 'content'), $result);
    $form_state->setValue('mollom', array_merge($mollom, $result));

    // Ensure the latest content ID is output as value.
    // form_set_value() is effectless, as this is not a element-level but a
    // form-level validation handler.
    $form['mollom']['contentId']['#value'] = $result['contentId'];

    // Prepare watchdog message teaser text.
    $teaser = '--';
    if (isset($data['postTitle'])) {
      $teaser = Unicode::truncate(strip_tags($data['postTitle']), 40);
    }
    elseif (isset($data['postBody'])) {
      $teaser = Unicode::truncate(strip_tags($data['postBody']), 40);
    }

    // Handle the profanity check result.
    if (isset($result['profanityScore']) && $result['profanityScore'] >= 0.5) {
      if ($mollom['discard']) {
        $form_state->setError($form, t('Your submission has triggered the profanity filter and will not be accepted until the inappropriate language is removed.'));
      }
      else {
        $form_state->setValue(['mollom', 'require_moderation'], TRUE);
      }
      Logger::addMessage(array(
        'message' => 'Profanity: %teaser',
        'arguments' => array('%teaser' => $teaser),
      ));
    }

    // Handle the spam check result.
    // The Mollom API takes over state tracking for each content ID/session. The
    // spamClassification will usually turn into 'ham' after solving a CAPTCHA.
    // It may also change to 'spam', if the user replaced the values with very
    // spammy content. In any case, we always do what we are told to do.
    $form_state->setValue(['mollom', 'require_captcha'], FALSE);
    $form['mollom']['captcha']['#access'] = FALSE;

    if (isset($result['spamClassification'])) {
      switch ($result['spamClassification']) {
        case 'ham':
          $message = SafeMarkup::format('Ham: %teaser', array('%teaser' => $teaser));
          \Drupal::logger('mollom')->notice($message);
          break;

        case 'spam':
          if ($mollom['discard']) {
            $form_state->setError($form, t('Your submission has triggered the spam filter and will not be accepted. @fp_message', array(
              '@fp_message' => MollomUtilities::formatFalsePositiveMessage($form_state, $data),
            )));
          }
          else {
            $form_state->setValue(array('mollom', 'require_moderation'), TRUE);
          }
          $message = SafeMarkup::format('Spam: %teaser', array('%teaser' => $teaser));
          \Drupal::logger('mollom')->notice($message);
          break;

        case 'unsure':
          if ($mollom['unsure'] == 'moderate') {
            $form_state->setValue(array('mollom', 'require_moderation'), TRUE);
          }
          else {
            $form_state->setValue(['mollom', 'captcha_response_id'], NULL);
            $form['mollom']['captcha_response_id']['#value'] = NULL;
            $form_state->setValue(array('mollom', 'require_captcha'), TRUE);
            // Require a new CAPTCHA and throw an error.
            $had_captcha = $form_state->get('mollom_had_captcha');
            $form_state->setCached(FALSE);
            // Set the CAPTCHA type required indicator.
            $form_state->setValue(array('mollom', 'captcha_required'), $mollom['captcha_type']);
            $form['mollom']['captcha_required']['#value'] = $mollom['captcha_type'];
            $form['mollom']['captcha']['#access'] = TRUE;
            if (!empty($had_captcha)) {
              $form_state->setErrorByName('mollom][captcha', t('The word verification was not completed correctly. Please complete this new word verification and try again.  @fp_message', array(
                '@fp_message' => MollomUtilities::formatFalsePositiveMessage($form_state, $data),
              )));
            }
            else {
              $form_state->setErrorByName('mollom][captcha', t('To complete this form, please complete the word verification.'));
            }
          }
          $message = SafeMarkup::format('Unsure: %teaser', array('%teaser' => $teaser));
          \Drupal::logger('mollom')->notice($message);
          break;
        case 'unknown':
        default:
          // If we end up here, Mollom responded with a unknown spamClassification.
          // Normally, this should not happen, but if it does, log it. As there
          // could be multiple reasons for this, it is not safe to trigger the
          // fallback mode.
          $message = SafeMarkup::format('Unknown: %teaser', array('%teaser' => $teaser));
          \Drupal::logger('mollom')->notice($message);
          break;
      }
    }
  }

  /**
   * Form validation handler to perform post-validation tasks.
   */
  public static function validatePost(&$form, FormState $form_state) {
    // Retain a post instead of discarding it. If 'discard' is FALSE, then the
    // 'moderation callback' is responsible for altering $form_state in a way that
    // the post ends up in a moderation queue. Most callbacks will only want to
    // set or change a value in $form_state.
    $mollom = $form_state->getValue('mollom');
    if ($mollom['require_moderation']) {
      $function = $mollom['moderation callback'];
      if (!empty($function) && is_callable($function)) {
        call_user_func_array($function, array(&$form, $form_state));
      }
    }
  }

  /**
   * Determine if Mollom validation should be run.
   *
   * This is required because even with limit_validation_errors set, Drupal
   * will still run the form validation (and then just not display the errors).
   * We don't want to contact Mollom to validate a CAPTCHA when the request is
   * simply to retrieve a new one.
   */
  protected static function shouldValidate(array $form, FormStateInterface $form_state) {
    // If the triggering element is within the captcha element, then no Mollom
    // validation should even run because it's pulling a new captcha element.
    return !Mollom::isCaptchaRefreshProcessing($form_state) && !Mollom::isCaptchaSwitchProcessing($form_state);
  }

  /**
   * Form submit handler to flush Mollom session and form information from cache.
   *
   * This is necessary as the entity forms will no longer automatically save
   * the data with the entity.
   *
   * @todo: Possible problems:
   *   - This submit handler is invoked too late; the primary submit handler might
   *     send out e-mails directly after saving the entity (e.g.,
   *     user_register_form_submit()), so mollom_mail_alter() is invoked before
   *     Mollom session data has been saved.
   */
  public static function submitForm($form, FormState $form_state) {
    // Some modules are implementing multi-step forms without separate form
    // submit handlers. In case we reach here and the form will be rebuilt, we
    // need to defer our submit handling until final submission.
    $is_rebuilding = $form_state->isRebuilding();
    if ($is_rebuilding) {
      return;
    }

    $mollom = $form_state->getValue('mollom');
    $form_object = $form_state->getFormObject();
    // If an 'entity' and a 'post_id' mapping was provided via
    // hook_mollom_form_info(), try to automatically store Mollom session data.
    if (empty($mollom) || empty($mollom['entity']) || !($form_state->getFormObject() instanceof EntityFormInterface)) {
      return;
    }
    /* @var $form_object \Drupal\Core\Entity\EntityFormInterface */
    $entity_id = $form_object->getEntity()->id();
    $data = (object) $mollom;
    $data->id = $entity_id;
    $data->moderate = $mollom['require_moderation'] ? 1 : 0;
    $stored_data = ResponseDataStorage::save($data);
    $form_state->setValue(['mollom', 'data'], $stored_data);
  }
}
