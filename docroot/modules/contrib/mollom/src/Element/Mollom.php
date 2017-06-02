<?php

namespace Drupal\mollom\Element;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\mollom\Entity\FormInterface;

/**
 * Provides a form element for storage of internal information.
 *
 * @FormElement("mollom")
 */
class Mollom extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => FALSE,
      '#process' => array(
        array($class, 'processMollom'),
      ),
      '#tree' => TRUE,
      '#pre_render' => array(
        array($class, 'preRenderMollom'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return $input;
  }

  /**
   * #pre_render callback for #type 'mollom'.
   *
   * - Hides the CAPTCHA if it is not required or the solution was correct.
   * - Marks the CAPTCHA as required.
   */
  public static function preRenderMollom(array $element) {
    // If a CAPTCHA was solved, then no need to continue to display.
    if (isset($element['captcha']['#solved']) && $element['captcha']['#solved'] === TRUE) {
      $element['captcha']['#access'] = FALSE;
    }
    else {
      // Add the CAPTCHA if required, or hide the element if not required.
      if (!empty($element['captcha_required']['#value'])) {
        Mollom::addMollomCaptcha($element);
      }
      else {
        $element['captcha']['#access'] = FALSE;
      }
    }

    // UX: Empty the CAPTCHA field value, as the user has to re-enter a new one.
    $element['captcha']['captcha_input']['#value'] = '';

    // DX: Debugging helpers.
    //$element['#suffix'] = 'contentId: ' . $element['contentId']['#value'] . '<br>';
    //$element['#suffix'] .= 'captchaId: ' . $element['captchaId']['#value'] . '<br>';

    return $element;
  }

  /**
   * Get the HTML markup for a Mollom CAPTCHA and add it to the Mollom element.
   *
   * @param array $element
   *   The Mollom custom form element passed by reference.
   */
  public static function addMollomCaptcha(&$element) {
    // Load the CAPTCHA from the Mollom API.
    $data = array(
      'type' => in_array($element['captcha_required']['#value'], array('image', 'audio')) ? $element['captcha_required']['#value'] : 'image',
      'ssl' => (int) (!empty($_SERVER['HTTPS']) && Unicode::strtolower($_SERVER['HTTPS']) !== 'off'),
    );
    // If the requested type is audio, make sure it is enabled for the site.
    if ($data['type'] == 'audio') {
      if (!\Drupal::config('mollom.settings')->get('captcha.audio.enabled')) {
        $data['type'] = 'image';
      }
    }
    if (!empty($element['contentId']['#value'])) {
      $data['contentId'] = $element['contentId']['#value'];
    }

    /** @var \Drupal\mollom\API\DrupalClient $mollom */
    $mollom_service = \Drupal::service('mollom.client');
    $captcha_result = $mollom_service->createCaptcha($data);

    // Add a log message to prevent the request log from appearing without a
    // message on CAPTCHA-only protected forms.
    \Drupal::logger('mollom')->notice('Retrieved new CAPTCHA: @id', array('@id' => isset($captcha_result['id']) ? $captcha_result['id'] : 'error'));

    // Check the response is valid.
    if (is_array($captcha_result) && isset($captcha_result['url'])) {
      $element['response']['captcha']['#value'] = $captcha_result;
    }
    else {
      $element['captcha']['#access'] = FALSE;
      \Drupal\mollom\Utility\MollomUtilities::handleFallback();
      $element['captchaId']['#value'] = 'invalid';
      return FALSE;
    }
    // Theme CAPTCHA output.
   // $element['captcha']['#theme'] = 'mollom_captcha';
    $element['captcha']['captcha_display'] = array(
      '#theme' => $data['type'] == 'audio' ? 'mollom_captcha_audio' : 'mollom_captcha_image',
      '#captcha_url' => $captcha_result['url'],
      '#weight' => 20,
    );

    // Add the CAPTCHA and its data to the element.
    $element['captcha']['#access'] = TRUE;
    //$element['captcha']['#field_prefix'] = $captcha_rendered;
    $element['captcha']['captcha_input']['#attributes'] = array('title' => t('Enter the characters from the verification above.'));

    // The mollom.swfobject library is only added if swfobject is available on the site.
    // @see mollom_library_info_build().
    if (\Drupal::service('library.discovery')->getLibraryByName('mollom', 'mollom.swfobject')) {
      $element['captcha']['#attached']['library'][] = 'mollom/mollom.swfobject';
    }

    // Ensure that the latest CAPTCHA ID is output as value.
    $element['captchaId']['#value'] = $captcha_result['id'];

    // The form element cannot be marked as #required, since _form_validate()
    // would throw an element validation error on an empty value otherwise,
    // before the form-level validation handler is executed.
    // #access cannot default to FALSE, since the $form may be cached, and
    // Form API ignores user input for all elements that are not accessible.
    $element['captcha']['captcha_input']['#required'] = TRUE;
    return !empty($captcha_result['url']);
  }

  public static function refreshCaptcha(array $form, FormStateInterface &$form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback for refreshing a CAPTCHA.
   */
  public static function captchaAjaxCallback($form, &$form_state) {
    return $form['mollom'];
  }

  /**
   * #process callback for #type 'mollom'.
   *
   * Mollom supports two fundamentally different protection modes:
   * - For text analysis, the state of a post is essentially tracked by the Mollom
   *   API/backend:
   *   - Every form submission attempt (re-)sends the post data to Mollom, and the
   *     API ensures to return the correct spamClassification each time.
   *   - The client-side logic fully relies on the returned spamClassification
   *     value to trigger the corresponding actions and does not track the state
   *     of the form submission attempt locally.
   *   - For example, when Mollom is "unsure" and the user solved the CAPTCHA,
   *     then subsequent Content API responses will return "ham" instead of
   *     "unsure", so the user is not asked to solve more than one CAPTCHA.
   * - For CAPTCHA-only, the solution state of a CAPTCHA has to be tracked locally:
   *   - Unlike text analysis, a CAPTCHA can only be solved or not. Additionally,
   *     a CAPTCHA cannot be solved more than once. The Mollom API only returns
   *     (once) whether a CAPTCHA has been solved correctly. A previous state
   *     cannot be queried from Mollom.
   *   - State-tracking would not be necessary, if there could not be other form
   *     validation errors preventing the form from submitting directly, as well as
   *     "Preview" buttons that may rebuild the entire form from scratch (if there
   *     are no validation errors).
   *   - To track state, the Form API cache is enabled, which allows to store and
   *     retrieve the entire $form_state of a previous submission (attempt).
   *   - Furthermore, page caching is force-disabled, so as to ensure that cached
   *     form data is not re-used by different users/visitors.
   *   - In combination with the Form API cache, this is essentially equal to
   *     force-starting a session for all users that try to submit a CAPTCHA-only
   *     protected form. However, a session would persist across other pages.
   *
   * @see mollom_form_alter()
   * @see mollom_element_info()
   * @see mollom_pre_render_mollom()
   */
  public static function processMollom(array $element, FormStateInterface $form_state, array $form) {
    $mollom = $form_state->getValue('mollom');
    $mollom = $mollom ? $mollom : [];
    // Allow overriding via hook_form_alter to set mollom override properties.
    if (isset($form['#mollom']) && is_array($form['#mollom'])) {
      $mollom += $form['#mollom'];
    }

    // Setup initial Mollom session and form information.
    $mollom += array(
      // Only TRUE if the form is protected by text analysis.
      'require_analysis' => $element['#mollom_form']['mode'] == FormInterface::MOLLOM_MODE_ANALYSIS,
      // Becomes TRUE whenever a CAPTCHA needs to be solved.
      'require_captcha' => $element['#mollom_form']['mode'] == FormInterface::MOLLOM_MODE_CAPTCHA,
      // The type of CAPTCHA to initially show; 'image' or 'audio'.
      'captcha_type' => 'image',
      // Becomes TRUE if the form is protected by text analysis and the submitted
      // entity should be unpublished.
      'require_moderation' => FALSE,
      // Internally used bag for last Mollom API responses.
      'response' => array(
      ),
    );

    $mollom_form_array = $element['#mollom_form'];
    $mollom += $mollom_form_array;

    // By default, bad form submissions are discarded, unless the form was
    // configured to moderate bad posts. 'discard' may only be FALSE, if there is
    // a valid 'moderation callback'. Otherwise, it must be TRUE.
    if (empty($mollom['moderation callback']) || !function_exists($mollom['moderation callback'])) {
      $mollom['discard'] = TRUE;
    }

    $form_state->setValue('mollom', $mollom);

    // Add the Mollom analysis library.
    $element['#attached']['library'][] = 'mollom/mollom.analysis';
    $element['#theme_wrappers'] = array('container');
    $element['#attributes']['id'] = 'mollom';

    // Add the Mollom session data elements.
    // These elements resemble the {mollom} database schema. The form validation
    // handlers will pollute them with values returned by Mollom. For entity
    // forms, the submitted values will appear in a $entity->mollom property,
    // which in turn represents the Mollom session data record to be stored.
    $element['entity'] = array(
      '#type' => 'value',
      '#value' => isset($mollom['entity']) ? $mollom['entity'] : 'mollom_content',
    );
    $element['id'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );
    $element['contentId'] = array(
      '#type' => 'hidden',
      // There is no default value; Form API will always use the value that was
      // submitted last (including rebuild scenarios).
      '#attributes' => array('class' => array('mollom-content-id')),
    );
    $element['captchaId'] = array(
      '#type' => 'hidden',
      '#attributes' => array('class' => array('mollom-captcha-id')),
    );
    $element['captcha_response_id'] = array(
      '#type' => 'hidden',
    );
    $element['form_id'] = array(
      '#type' => 'value',
      '#value' => $mollom['id'],
    );
    $element['passed_validation'] = array(
      '#type' => 'value',
      '#value' => FALSE,
    );
    // Determine the next captcha type to show either via AJAX callback
    // submission or through form state.
    if (static::isCaptchaSwitchProcessing($form_state)) {
      $captcha_type = $form_state->getValue(array('mollom','captcha_required')) == 'image' ? 'audio' : 'image';
    }
    else {
      $captcha_type = $form_state->getValue(array('mollom', 'captcha_required'));
    }
    $element['captcha_required'] = array(
      '#type' => 'hidden',
      '#value' => $captcha_type,
    );
    $data_spec = array(
      '#type' => 'value',
      '#value' => NULL,
    );
    $element['spamScore'] = $data_spec;
    $element['spamClassification'] = $data_spec;
    $element['qualityScore'] = $data_spec;
    $element['profanityScore'] = $data_spec;
    $element['languages'] = $data_spec;
    $element['reason'] = $data_spec;
    $element['solved'] = $data_spec;

    // Add the CAPTCHA containing element.
    // - Input cannot be #required, since that would cause _form_validate() to
    //   output a validation error in situations in which the CAPTCHA is not
    //   required.
    // - #access can also not start with FALSE, since the form structure may be
    //   cached, and Form API ignores all user input for inaccessible elements.
    // Since this element needs to be hidden by the #pre_render callback, but that
    // callback does not have access to $form_state, the 'passed_captcha' state is
    // assigned as Boolean #solved = TRUE element property when solved correctly.
    $element['captcha'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'mollom-captcha',
      ),
      '#theme' => 'mollom_captcha',
    );
    $element['captcha']['refresh'] = array(
      '#type' => 'submit',
      '#name' => 'mollom_refresh_captcha',
      '#value' => t('Refresh'),
      '#attributes' => array(
        'class' => array('mollom-refresh-captcha'),
      ),
      '#ajax' => array(
        'callback' => 'Drupal\mollom\Element\Mollom::captchaAjaxCallback',
        'wrapper' => 'mollom',
        'progress' => array(
          'type' => '',
          'message' => '',
        ),
      ),
      '#limit_validation_errors' => array(array('mollom')),
      '#submit' => array('Drupal\mollom\Element\Mollom::refreshCaptcha'),
      '#weight' => 10,
    );
    $element['captcha']['captcha_input'] = array(
      '#type' => 'textfield',
      '#title' => t('Verification'),
      '#size' => 10,
      '#default_value' => '',
      '#weight' => 40,
    );
    $element['captcha']['switch'] = array(
      '#type' => 'submit',
      '#name' => 'mollom_switch_captcha',
      '#value' => t('Switch verification'),
      '#attributes' => array(
        'class' => array('mollom-switch-captcha'),
      ),
      '#ajax' => array(
        'callback' => 'Drupal\mollom\Element\Mollom::captchaAjaxCallback',
        'wrapper' => 'mollom',
      ),
      '#limit_validation_errors' => array(array('mollom')),
      '#submit' => array('Drupal\mollom\Element\Mollom::refreshCaptcha'),
      '#weight' => 30,
    );

    // Disable browser autocompletion, unless testing mode is enabled, in which
    // case autocompletion for 'correct' and 'incorrect' is handy.
    $testing_mode = \Drupal::config('mollom.settings')->get('test_mode.enabled');
    if (!$testing_mode) {
      $element['captcha']['captcha_input']['#attributes']['autocomplete'] = 'off';
    }

    // For CAPTCHA-only protected forms:
    if (!$mollom['require_analysis'] && $mollom['require_captcha']) {
      // Retrieve and show an initial CAPTCHA.
      if (!$form_state->isProcessingInput()) {
        $element['captcha_required']['#value'] = $mollom['captcha_type'];
      }
    }
    // If the CAPTCHA was solved in a previous submission already, resemble
    // mollom_validate_captcha(). This case is only reached in case the form
    // 1) is not cached, 2) fully validated, 3) was submitted, and 4) is
    // getting rebuilt; e.g., "Preview" on comment and node forms.
    if (!empty($form_state->getValue(['mollom', 'captcha_response_id']))) {
      $element['captcha']['#solved'] = TRUE;
    }
    // Add a type indicator to allow instructions to be updated on the client.
    // NOTE: If the value of the button is updated on the server then it
    // no longer matches when comparing to determine the triggering_element.
    $audio_enabled = \Drupal::config('mollom.settings')->get('captcha.audio.enabled');
    $show_audio = $audio_enabled && $element['captcha_required']['#value'] == 'audio';
    $element['captcha']['#attributes']['data-mollom-captcha-next'] = $show_audio ? 'image' : 'audio';
    if (!$audio_enabled) {
      $element['captcha']['switch']['#access'] = FALSE;
    }

    // Add a spambot trap. Purposefully use 'homepage' as field name.
    // This form input element is only supposed to be visible for robots. It has
    // - no label, since some screen-readers do not notice that the label is
    //   attached to an input that is hidden.
    // - no 'title' attribute, since some JavaScript libraries that are trying to
    //   mimic HTML5 placeholders are injecting the 'title' into the input's value
    //   and fail to clean up and remove the placeholder value upon form submission,
    //   causing false-positive spam classifications.
    $element['homepage'] = array(
      '#type' => 'textfield',
      '#title' => t('Home page'),
      // Wrap the entire honeypot form element markup into a hidden container, so
      // robots cannot simply check for a style attribute, but instead have to
      // implement advanced DOM processing to figure out whether they are dealing
      // with a honeypot field.
      '#prefix' => '<div class="mollom-homepage">',
      '#suffix' => '</div>',
      '#default_value' => '',
      '#attributes' => array(
        // Disable browser autocompletion.
        'autocomplete' => 'off',
      ),
    );

    // Add link to privacy policy on forms protected via textual analysis,
    // if enabled.
    if ($mollom_form_array['mode'] == FormInterface::MOLLOM_MODE_ANALYSIS && \Drupal::config('mollom.settings')->get('privacy_link')) {
      $element['privacy'] = array(
        '#prefix' => '<div class="description mollom-privacy">',
        '#suffix' => '</div>',
        '#markup' => t('By submitting this form, you accept the <a href="@privacy-policy-url" class="mollom-target" rel="nofollow">Mollom privacy policy</a>.', array(
          '@privacy-policy-url' => 'https://mollom.com/web-service-privacy-policy',
        )),
        '#weight' => 10,
      );
    }

    // Make Mollom form and session information available to entirely different
    // functions.
    // @see mollom_mail_alter()
    /*$GLOBALS['mollom'] = &$form_state['mollom'];*/

    return $element;
  }

  /**
   * Checks a form submission to see if the triggering element was a request
   * to pull a new CAPTCHA.
   *
   * @param FormStateInterface $form_state
   *   The current form state.
   * @return bool
   *   TRUE if the submission is for a CAPTCHA refresh, FALSE otherwise.
   */
  public static function isCaptchaRefreshProcessing(FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return $trigger['#name'] === 'mollom_refresh_captcha';
  }

  /**
   * Checks a form submission to see if the triggering element was a request
   * to pull a new CAPTCHA of a different type.
   *
   * @param FormStateInterface $form_state
   *   The current form state.
   * @return bool
   *   TRUE if the submission is for a CAPTCHA refresh, FALSE otherwise.
   */
  public static function isCaptchaSwitchProcessing(FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return $trigger['#name'] === 'mollom_switch_captcha';
  }
}
