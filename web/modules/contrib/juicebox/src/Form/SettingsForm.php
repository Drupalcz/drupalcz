<?php

/**
 * @file
 * Contains \Drupal\devel\Form\SettingsForm.
 */

namespace Drupal\juicebox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines a form that configures global Juicebox settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'juicebox_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'juicebox.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $library = \Drupal::service('juicebox.formatter')->getLibrary(TRUE, TRUE);
    $version = !empty($library['version']) ? $library['version'] : t('Unknown');
     // Get all settings
    $settings = $this->config('juicebox.settings')->get();
    // If the base language list is not officially saved yet, we can get the
    // default value from the library settings.
    if (empty($settings['base_languagelist'])) {
      $settings['base_languagelist'] = $library['base_languagelist'];
    }
    $form['juicebox_admin_intro'] = array(
      '#markup' => t("The options below apply to all Juicebox galleries. Note that most Juicebox configuration options are set within each gallery's unique configuration form and not applied on a global scope like the values here."),
    );
    $form['apply_markup_filter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Filter all title and caption output for compatibility with Juicebox javascript (recommended)'),
      '#default_value' => $settings['apply_markup_filter'],
      '#description' => t('This option helps ensure title/caption output is syntactically compatible with the Juicebox javascript library by removing block-level tags.'),
    );
    $form['enable_cors'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow galleries to be embedded remotely (CORS support)'),
      '#default_value' => $settings['enable_cors'],
      '#description' => t('Enable cross-origin resource sharing (CORS) for all generated Juicebox XML. This will allow all origins/domains to use any Juicebox XML requested from this site for embedding purposes (adds a <em>Access-Control-Allow-Origin: *</em> header to all Juicebox XML responses).'),
    );
    $form['multilingual'] = array(
      '#type' => 'details',
      '#title' => t('Multilingual options'),
      '#open' => TRUE,
    );
    $form['multilingual']['translate_interface'] = array(
      '#type' => 'checkbox',
      '#title' => t('Translate the Juicebox javascript interface'),
      '#default_value' => $settings['translate_interface'],
      '#description' => t('Send interface strings to the Juicebox javascript after passing them through the Drupal translation system.'),
    );
    $form['multilingual']['base_languagelist'] = array(
      '#type' => 'textarea',
      '#title' => t('Base string for interface translation'),
      '#default_value' => $settings['base_languagelist'],
      '#description' => t('The base <strong>English</strong> interface text that Drupal should attempt to translate and pass to the Juicebox javascript for display (using the "languageList" configuration option). This text will be treated as a <strong>single string</strong> by Drupal and must be translated with a tool such as the Locale module. Note that this base string value will rarely change, and any changes made to it will break existing translations.'),
      '#wysiwyg' => FALSE,
      '#states' => array(
        // Hide the settings when the translate option is disabled.
        'invisible' => array(
          ':input[name="translate_interface"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['multilingual']['base_languagelist_suggestion'] = array(
      '#type' => 'item',
      '#title' => t('Suggested base string for currently detected Juicebox version (@version)', array('@version' => $version)),
      '#description' => SafeMarkup::format('<pre>' . $library['base_languagelist'] . '</pre>', array()),
      '#states' => array(
        // Hide the settings when the translate option is disabled.
        'invisible' => array(
          ':input[name="translate_interface"]' => array('checked' => FALSE),
        ),
      ),
    );
    $multisize_disallowed = in_array('juicebox_multisize_image_style', $library['disallowed_conf']);
    $multisize_description = '<p>' . t('Some versions of the Juicebox javascript library support "multi-size" (adaptive) image delivery. Individual galleries configured to use the <strong>Juicebox PRO multi-size (adaptive)</strong> image style allow for three different source derivatives to be defined per image, each of which can be configured below. The Juicebox javascript library will then choose between these depending on the active screen mode (i.e. viewport size) of each user. See the Juicebox javascript library documentation for more information.') . '</p>';
    if ($multisize_disallowed) {
      $multisize_description .= '<p><strong>' . t('Your currently detected Juicebox version (@version) is not compatible with multi-size features, so the options below have been disabled.', array('@version' => $version)) . '</strong></p>';
    }
    // Mark our description, and its markup, as safe for unescaped display.
    $multisize_description = SafeMarkup::format($multisize_description, array());
    $form['juicebox_multisize'] = array(
      '#type' => 'details',
      '#title' => t('Juicebox PRO multi-size style configuration'),
      '#description' => $multisize_description,
      '#open' => !$multisize_disallowed,
    );
    // Get available image style presets
    $presets = image_style_options(FALSE);
    $form['juicebox_multisize']['juicebox_multisize_small'] = array(
      '#title' => t('Small mode image style'),
      '#default_value' => $settings['juicebox_multisize_small'],
      '#description' => t('The style formatter to use in small screen mode (e.g., non-retina mobile devices).'),
    );
    $form['juicebox_multisize']['juicebox_multisize_medium'] = array(
      '#title' => t('Medium mode image style'),
      '#default_value' => $settings['juicebox_multisize_medium'],
      '#description' => t('The style formatter to use in medium screen mode (e.g., desktops and retina mobile devices).'),
    );
    $form['juicebox_multisize']['juicebox_multisize_large'] = array(
      '#title' => t('Large mode image style'),
      '#default_value' => $settings['juicebox_multisize_large'],
      '#description' => t('The style formatter to use in large screen mode (e.g., expanded view and retina laptops).'),
    );
    foreach ($form['juicebox_multisize'] as &$options) {
      if (is_array($options)) {
        $options += array(
          '#type' => 'select',
          '#options' => $presets,
          '#empty_option' => t('None (original image)'),
          '#disabled' => $multisize_disallowed,
        );
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('juicebox.settings')
      ->set('apply_markup_filter', $form_state->getvalue('apply_markup_filter'))
      ->set('enable_cors', $form_state->getvalue('enable_cors'))
      ->set('translate_interface', $form_state->getvalue('translate_interface'))
      ->set('base_languagelist', $form_state->getvalue('base_languagelist'))
      ->set('juicebox_multisize_small', $form_state->getvalue('juicebox_multisize_small'))
      ->set('juicebox_multisize_medium', $form_state->getvalue('juicebox_multisize_medium'))
      ->set('juicebox_multisize_large', $form_state->getvalue('juicebox_multisize_large'))
      ->save();
    // These settings are global and may affect any gallery embed or XML code,
    // so we need to clear everything tagged with juicebox_gallery cache tag.
    Cache::invalidateTags(array('juicebox_gallery'));
    drupal_set_message(t('The Juicebox configuration options have been saved'));
  }

}
