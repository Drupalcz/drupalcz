<?php

/**
 * @file
 * Contains \Drupal\jssor\Plugin\Field\FieldFormatter\JssorFieldFormatter.
 */

namespace Drupal\jssor\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Plugin for responsive image formatter.
 *
 * @FieldFormatter(
 *   id = "jssor_formatter",
 *   label = @Translation("Jssor Gallery"),
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class JssorFieldFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /*
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Constructs a ResponsiveImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $image_style_storage, LinkGeneratorInterface $link_generator, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->imageStyleStorage = $image_style_storage;
    $this->linkGenerator = $link_generator;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('link_generator'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => '',
      'autoplay' => TRUE,
      'autoplayinterval' => 3000,
      'arrownavigator' => TRUE,
      'bulletnavigator' => TRUE,
      'caption' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_options = array();
    $image_styles = $this->imageStyleStorage->loadMultiple();
    if ($image_styles && !empty($image_styles)) {
      foreach ($image_styles as $machine_name => $image_style) {
          $image_options[$machine_name] = $image_style->label();
      }
    }

    $element['image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#required' => TRUE,
      '#options' => $image_options,
    );

    $element['autoplay'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable to auto play.'),
      '#default_value' => $this->getSetting('autoplay'),
    );

    $element['arrownavigator'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Arrow navigator'),
      '#default_value' => $this->getSetting('arrownavigator'),
      '#description' => t('Enable arrow navigator.'),
    );

    $element['bulletnavigator'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Bullet navigator'),
      '#default_value' => $this->getSetting('bulletnavigator'),
      '#description' => t('Enable bullet navigator.'),
    );

    $element['caption'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Caption'),
      '#default_value' => $this->getSetting('caption'),
      '#description' => t('Enable caption.'),
    );

    $element['autoplayinterval'] = array(
      '#type' => 'number',
      '#title' => $this->t('Autoplay interval'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => $this->getSetting('autoplayinterval'),
      ),
      '#description' => t('Interval (in milliseconds) to go for next slide since the previous stopped.'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];

    $image_style = $this->imageStyleStorage->load($settings['image_style']);
    if ($image_style) {
      $summary[] = t('Image style: @image_style', array('@image_style' => $image_style->label()));
    }

    if ($this->getSetting('arrownavigator')) {
      $summary[] = t('Arrow navigator');
    }

    if ($this->getSetting('bulletnavigator')) {
      $summary[] = t('Bullet navigator');
    }

    if ($this->getSetting('caption')) {
      $summary[] = t('Caption');
    }

    $summary[] = t('Autoplay : @autoplay', array('@autoplay' => $settings['autoplay']));
    $summary[] = t('Autoplay interval : @autoplayinterval', array('@autoplayinterval' => $settings['autoplayinterval']));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $entity = $items->getEntity();

    $field_instance = $items->getFieldDefinition();

    $entity_type_id = $entity->getEntityTypeId();

    $entity_id = $entity->id();

    $field_name = $field_instance->getName();

    $display_name = $this->viewMode;

    $files = $this->getEntitiesToView($items, $langcode);

    $settings = $this->getSettings();

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->urlInfo();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = array();
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($image_uri));
      }
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = array(
        '#theme' => 'image_jssor_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#caption' => $this->getSetting('caption'),
        '#url' => $url,
        '#settings' => $settings,
        '#cache' => array(
          'tags' => $cache_tags,
        ),
      );

    }

    $container = array(
      '#theme' => 'images_jssor_formatter',
      '#children' => $elements,
      '#settings' => $settings,
      '#attributes' => array(
        'class' => array('slider'),
        'id' => array('slider-dom-id-1'),
      ),
    );

    // Attach library.
    $container['#attached']['library'][] = 'jssor/jquery.jssor.slider';

    $settings = [];

    // ID.
    // @todo generate random ?
    $settings['view_dom_id'] = '1';

    // Global settings.
    $settings['$ArrowKeyNavigation'] = TRUE;

    // Arrow navigator.
    if ($this->getSetting('arrownavigator')) {
      $settings['$ArrowNavigatorOptions'] = array(
        '$Class' => '$JssorArrowNavigator$',
        '$ChanceToShow' => 1, // Mouse Over.
        '$AutoCenter' =>  2, // Vertical.
        '$Scale' => TRUE,
      );
    }

    // Bullet navigator.
    if ($this->getSetting('bulletnavigator')) {
      $settings['$BulletNavigatorOptions'] = array(
        '$Class' => '$JssorBulletNavigator$',
        '$ChanceToShow' => 2, // Always.
        '$AutoCenter' =>  1, // Vertical.
        '$Scale' => TRUE,
      );
    }

    // Attach settings.
    $container['#attached']['drupalSettings']['views']['jssorViews'][] = $settings;

    return $container;
  }
}
