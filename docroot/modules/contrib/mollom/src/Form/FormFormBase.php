<?php

namespace Drupal\mollom\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\mollom\Entity\FormInterface;
use Drupal\mollom\Utility\MollomUtilities;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mollom\Controller\FormController;

/**
 * Class FormFormBase.
 * @package Drupal\mollom\Form
 * @ingroup mollom
 */
class FormFormBase extends EntityForm {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Construct the FormFormBase.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   Retrieves user permission information to determine bypass permissions.
   */
  public function __construct(PermissionHandlerInterface $permission_handler) {
    $this->permissionHandler = $permission_handler;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need form the base class.
    $form = parent::buildForm($form, $form_state);

    // Display any API key errors.
    MollomUtilities::getAdminAPIKeyStatus();
    MollomUtilities::displayMollomTestModeWarning();

    /* @var $entity \Drupal\mollom\Entity\FormInterface */
    $entity = $this->getEntity();
    $form_id = '';
    if ($entity->isNew()) {
      // Determine if the form id selection just changed.
      $input = $form_state->getUserInput();
      if (!empty($input['id'])) {
        $form_id = $input['id'];
        $mollom_form = $entity->initialize($form_id);
      }
      else if ($query_form_id = \Drupal::request()->query->get('form_id', '')) {
        $form_id = $query_form_id;
        $mollom_form = $entity->initialize($form_id);
      }
    }
    else {
      $form_id = $entity->id();
      $mollom_form = $entity->initialize();
    }

    $enabled_fields = [];
    if ($entity->isNew() && !empty($input['id'])) {
      foreach ($mollom_form['enabled_fields'] as $value) {
        $enabled_fields[] = rawurlencode($value);
      }
      // Set defaults back.
      // See https://www.drupal.org/node/1100170
      $input['checks'] = $entity->getChecks();
      $input['enabled_fields'] = $enabled_fields;
      $form_state->setUserInput($input);
    } else {
      foreach ($entity->getEnabledFields() as $value) {
        $enabled_fields[] = rawurldecode($value);
      }
    }

    // Build the form.
    if ($entity->isNew()) {
      $options = $this->getProtectableFormOptions();
      if (empty($options)) {
        return $this->redirect('entity.mollom_form.list');
      }
      $form['#attributes']['id'] = $this->getFormId();
      $form['id'] = array(
        '#type' => 'select',
        '#title' => $this->t('Mollom Form'),
        '#maxlength' => 255,
        '#options' => $options,
        '#default_value' => $form_id,
        '#empty_option' => t('Select a form to configure...'),
        '#required' => TRUE,
        '#ajax' => array(
          'callback' => array(
            $this,
            'ajaxFormHandler'
          ),
          'wrapper' => $this->getFormId(),
        ),
      );
      // Must select the form to protect prior to continuing.
      if (empty($form_id)) {
        return $form;
      }
    }
    else {
      $form['label'] = array(
        '#title' => t('Protected form'),
        '#type' => 'textfield',
        '#default_value' => $entity->label(),
        '#disabled' => TRUE,
      );
    }

    // Protection mode
    $modes = array(
      FormInterface::MOLLOM_MODE_ANALYSIS => $this->t('@option <em>(@recommended)</em>', array(
        '@option' => $this->t('Text analysis'),
        '@recommended' => $this->t('recommended'),
      )),
      FormInterface::MOLLOM_MODE_CAPTCHA => t('CAPTCHA only'),
    );

    $form['mode'] = array(
      '#type' => 'radios',
      '#title' => t('Protection mode'),
      '#options' => $modes,
      '#default_value' => isset($entity->mode) ? $entity->mode : key($modes),
    );
    $form['mode'][FormInterface::MOLLOM_MODE_ANALYSIS] = array(
      '#description' => t('Mollom will analyze the post and will only show a CAPTCHA when it is unsure.'),
    );
    $form['mode'][FormInterface::MOLLOM_MODE_CAPTCHA] = array(
      '#description' => t('A CAPTCHA will be shown for every post. Only choose this if there are too few text fields to analyze.'),
    );
    $form['mode'][FormInterface::MOLLOM_MODE_CAPTCHA]['#description'] .= '<br />' . t('Note: Page caching is disabled on all pages containing a CAPTCHA-only protected form.');


    $all_permissions = $this->permissionHandler->getPermissions();
    // Prepend Mollom's global permission to the list.
    if (empty($mollom_form['bypass access']) || !is_array($mollom_form['bypass access'])) {
      $mollom_form['bypass access'] = [];
    }
    array_unshift($mollom_form['bypass access'], 'bypass mollom protection');

    $permissions = array();
    if (isset($mollom_form['bypass access'])) {
      foreach ($mollom_form['bypass access'] as $permission) {
        $permissions[Html::getClass($permission)] = array(
          'title' => $all_permissions[$permission]['title'],
          'url' => Url::fromRoute('user.admin_permissions'),
          'fragment' => 'module-' . $all_permissions[$permission]['provider'],
        );
      }
    }
    $form['mode']['#description'] = t('The protection is omitted for users having any of the permissions: @permission-list', array(
      '@permission-list' =>  \Drupal::theme()->render('links', array('links' => $permissions)),
    ));

    // Textual analysis filters.
    $form['checks'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Text analysis checks'),
      '#options' => array(
        'spam' => t('Spam'),
        'profanity' => t('Profanity'),
      ),
      '#default_value' => $entity->getChecks(),
      '#states' => array(
        'visible' => array(
          '[name="mode"]' => array('value' => (string) FormInterface::MOLLOM_MODE_ANALYSIS),
        ),
      ),
    );
    // Profanity check requires text to analyze; unlike the spam check, there
    // is no fallback in case there is no text.
    $form['checks']['profanity']['#access'] = !empty($mollom_form['elements']);

    // Form elements defined by hook_mollom_form_info() use the
    // 'parent][child' syntax, which Form API also uses internally for
    // form_set_error(), and which allows us to recurse into nested fields
    // during processing of submitted form values. However, since we are using
    // those keys also as internal values to configure the fields to use for
    // textual analysis, we need to encode them. Otherwise, a nested field key
    // would result in the following checkbox attribute:
    //   '#name' => 'mollom[enabled_fields][parent][child]'
    // This would lead to a form validation error, because it is a valid key.
    // By encoding them, we prevent this from happening:
    //   '#name' => 'mollom[enabled_fields][parent%5D%5Bchild]'
    $elements = array();
    if (isset($mollom_form['elements']) && is_array($mollom_form['elements'])) {
      foreach ($mollom_form['elements'] as $key => $value) {
        $elements[rawurlencode($key)] = $value;
      }
    }
    $enabled_field_selections = [];
    foreach($enabled_fields as $key => $value) {
      $enabled_field_selections[rawurlencode($key)] = rawurlencode($value);
    }
    $form['enabled_fields'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Text fields to analyze'),
      '#options' => $elements,
      '#default_value' => $enabled_field_selections,
      '#description' => t('Only enable fields that accept text (not numbers). Omit fields that contain sensitive data (e.g., credit card numbers) or computed/auto-generated values, as well as author information fields (e.g., name, e-mail).'),
      '#access' => !empty($mollom_form['elements']),
      '#states' => array(
        'visible' => array(
          '[name="mode"]' => array('value' => (string) FormInterface::MOLLOM_MODE_ANALYSIS),
        ),
      ),
    );

    $form['mapping'] = array(
      '#type' => 'value',
      '#value' => $mollom_form['mapping'],
    );

    if ($entity->isNew()) {
      $form['module'] = array(
        '#type' => 'value',
        '#value' => $mollom_form['module'],
      );
      $form['label'] = array(
        '#type' => 'value',
        '#value' => $mollom_form['title'],
      );
      $form['entity'] = array(
        '#type' => 'value',
        '#value' => $mollom_form['entity'],
      );
      $form['bundle'] = array(
        '#type' => 'value',
        '#value' => $mollom_form['bundle'],
      );
    }

    $form['strictness'] = array(
      '#type' => 'radios',
      '#title' => t('Text analysis strictness'),
      '#options' => array(
        'normal' => t('@option <em>(@recommended)</em>', array(
          '@option' => t('Normal'),
          '@recommended' => $this->t('recommended'),
        )),
        'strict' => t('Strict: Posts are more likely classified as spam'),
        'relaxed' => t('Relaxed: Posts are more likely classified as ham'),
      ),
      '#default_value' => $entity->getStrictness(),
      '#states' => array(
        'visible' => array(
          '[name="mode"]' => array('value' => (string) FormInterface::MOLLOM_MODE_ANALYSIS),
        ),
      ),
    );

    $form['unsure'] = array(
      '#type' => 'radios',
      '#title' => t('When text analysis is unsure'),
      '#default_value' => $entity->getUnsure(),
      '#options' => array(
        'captcha' => t('@option <em>(@recommended)</em>', array(
          '@option' => t('Show a CAPTCHA'),
          '@recommended' => $this->t('recommended'),
        )),
        'moderate' => t('Retain the post for manual moderation'),
        'binary' => t('Accept the post'),
      ),
      '#required' => $entity->getProtectionMode() == FormInterface::MOLLOM_MODE_ANALYSIS,
      // Only possible for forms protected via text analysis.
      '#states' => array(
        'visible' => array(
          '[name="mode"]' => array('value' => (string) FormInterface::MOLLOM_MODE_ANALYSIS),
          '[name="checks[spam]"]' => array('checked' => TRUE),
        ),
      ),
    );
    // Only possible for forms supporting moderation of unpublished posts.
    $form['unsure']['moderate']['#access'] = !empty($mollom_form['moderation callback']);

    $form['discard'] = array(
      '#type' => 'radios',
      '#title' => t('When text analysis identifies spam'),
      '#default_value' => $entity->getDiscard(),
      '#options' => array(
        1 => t('@option <em>(@recommended)</em>', array(
          '@option' => t('Discard the post'),
          '@recommended' => $this->t('recommended'),
        )),
        0 => t('Retain the post for manual moderation'),
      ),
      '#required' => $entity->getProtectionMode() == FormInterface::MOLLOM_MODE_ANALYSIS,
      // Only possible for forms supporting moderation of unpublished posts.
      //'#access' => !empty($mollom_form['moderation callback']),
      // Only possible for forms protected via text analysis.
      '#states' => array(
        'visible' => array(
          '[name="mode"]' => array('value' => (string) FormInterface::MOLLOM_MODE_ANALYSIS),
          '[name="checks[spam]"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Return the form.
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Make field checkboxes required, if protection mode is text analysis.
    // @see http://drupal.org/node/875722
    $mode = $form_state->getValue('mode');
    $required = $mode == FormInterface::MOLLOM_MODE_ANALYSIS;

    $form['checks']['#required'] = $required;
    $form['discard']['#required'] = $required;

    if ($required && !array_filter($form_state->getValue('checks'))) {
      $form_state->setErrorByName('checks', t('At least one text analysis check is required.'));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * Saves the entity. This is called after submit() has built the entity from
   * the form values. Do not override submit() as save() is the preferred
   * method for entity form controllers.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Only store a list of enabled textual analysis checks.
    /* @var $form \Drupal\mollom\Entity\FormInterface */
    $form = $this->entity;
    $form->setChecks(array_keys(array_filter($form_state->getValue('checks'))));
    // Prepare selected fields for storage.
    $enabled_fields = array();
    $selected_fields = $form_state->getValue('enabled_fields');
    foreach (array_keys(array_filter($selected_fields)) as $field) {
      $enabled_fields[] = rawurldecode($field);
    }
    $form->setEnabledFields($enabled_fields);

    $status = $form->save();

    $entity_label = $form->label();
    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      drupal_set_message($this->t('The form protection has been updated.'));
      \Drupal::logger('mollom')->notice('Mollom Form %label has been updated.', array('%label' => $entity_label));
    }
    else {
      // If we created a new entity...
      drupal_set_message($this->t('The form protection has been added.'));
      \Drupal::logger('mollom')->notice('Mollom form %label has been added.', array('%label' => $entity_label));
    }

    // Redirect the user to the following path after the save action.
    $form_state->setRedirect('entity.mollom_form.list');
  }


  /**
   * Return registered forms as an array suitable for a 'checkboxes' form element #options property.
   */
  protected function getProtectableFormOptions() {
    // Retrieve all registered forms.
    $form_list = FormController::getProtectableForms();

    // Remove already configured form ids.
    $result = $this->entity->loadMultiple();

    foreach ($result as $form_id) {
      unset($form_list[$form_id->id()]);
    }
    // If all registered forms are configured already, output a message, and
    // redirect the user back to overview.
    if (empty($form_list)) {
      drupal_set_message(t('All available forms are protected already.'));
    }

    // Load module information.
    $module_info = system_get_info('module');

    // Transform form information into an associative array suitable for #options.
    $options = array();
    foreach ($form_list as $form_id => $info) {
      // system_get_info() only supports enabled modules. Default to the module's
      // machine name in case it is disabled.
      $module = $info['module'];
      if (!isset($module_info[$module])) {
        $module_info[$module]['name'] = $module;
      }
      $options[$form_id] = t('@module: @form-title', array(
        '@form-title' => $info['title'],
        '@module' => t($module_info[$module]['name']),
      ));
    }
    // Sort form options by title.
    asort($options);

    return $options;
  }

  /**
   * AJAX submit handler called whenever the form id changes.
   */
  function ajaxFormHandler(array $form, FormStateInterface &$form_state) {
    return $form;
  }

}
