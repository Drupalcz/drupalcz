<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for views jssor rows templates.
 *
 * Default template: views-view-jssor.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - options: An array of options. Each option contains:
 *   - row: An array containing information about the current row.
 */
function template_preprocess_views_view_jssor(&$variables) {
  $view = $variables['view'];
  $rows = $variables['rows'];

  // Style plugin.
  $style = $view->style_plugin;
  $options = $style->options;

  // Row plugin.
  $row_plugin = $view->rowPlugin;
  $row_options = $row_plugin->options;

  // Attach library.
  $view->element['#attached']['library'][] = 'jssor/jquery.jssor.slider';

  $settings = [];

  // ID.
  $settings['view_dom_id'] = $view->dom_id;

  // Jssor Slider Options.

  // The way to fill image in slide, 0 stretch, 1 contain (keep aspect ratio and
  // put all inside slide), 2 cover (keep aspect ratio and cover whole slide),
  // 4 actual size, 5 contain for large image, actual size for small image,
  // default value is 0
  $settings['$FillMode'] = 0;

  // For image with lazy loading format (<IMG src2="url" .../>), by default it
  // will be loaded only when the slide comes.But an integer value
  // (maybe 1, 2 or 3) indicates that how far of nearby slides should be loaded
  // immediately as well, default value is 1.
  // $settings['$LazyLoading'] = 1;

  // Index of slide to display when initialize, default value is 0.
  // $settings['$StartIndex'] = 0;

  // Enable loop(circular) of carousel or not, 0: stop, 1: loop, 2 rewind,
  // default value is 1
  // $settings['$Loop'] = 1;

  // Allows keyboard (arrow key) navigation or not.
  $settings['$ArrowKeyNavigation'] = isset($options['general']['key_navigation']) ? $options['general']['key_navigation'] : FALSE;

  // Specifies default duration (swipe) for slide in milliseconds.
  $settings['$SlideDuration'] = isset($options['general']['slide_duration']) ? (int) $options['general']['slide_duration'] : 500;

  // Minimum drag offset to trigger slide.
  // $settings['$MinDragOffsetToSlide'] = 20;

  // Space between each slide in pixels.
  $settings['$SlideSpacing'] = $options['general']['slide_spacing'];;

  // Number of slides to display in the 'slides' container (slideshow would be
  // disabled if the value is greater than 1)
  $settings['$DisplayPieces'] = 2;

  // Orientation to drag slide.
  $settings['$DragOrientation'] = isset($options['general']['drag_orientation']) ? $options['general']['drag_orientation'] : 1;


  // Autoplay.
  if ($options['global']['autoplay'] === 1) {
    // Whether to play automatically, to enable slideshow, this option must be
    // set to true.
    $settings['$AutoPlay'] = ($options['global']['autoplay']) ? TRUE : FALSE;

    // Steps to go for each auto play request. Possible value can be 1, 2, -1, -2.
    $settings['$AutoPlaySteps'] = (int) $options['autoplay']['autoplaysteps'];

    // Interval (in milliseconds) to go for next slide since the previous stopped
    // if the slider is auto playing
    $settings['$AutoPlayInterval'] = (int) $options['autoplay']['autoplayinterval'];

    // Whether to pause when mouse over if a slider is auto playing, 0: no pause,
    // 1: pause for desktop, 2: pause for touch device, 3: pause for desktop and
    // touch device, 4: freeze for desktop, 8: freeze for touch device, 12:
    // freeze for desktop and touch device, default value is 1.
    $settings['$PauseOnHover'] = (int) $options['autoplay']['pauseonhover'];
  }


  // Slideshow.
  if (!empty($options['autoplay']['transition'] && $options['autoplay']['transition'] !== 'transition0000')) {
    $settings['$SlideshowOptions'] = array(
      '$Class' => '$JssorSlideshowRunner',
      'transition' => $options['autoplay']['transition'],
      '$TransitionsOrder' => 1,
      '$ShowLink' => FALSE,
    );
  }


  // Captions.
  if ($row_plugin->getPluginId() === 'jssor_row') {
    $settings['$CaptionSliderOptions'] = array(
      '$Class' => '$JssorCaptionSlider$',
      '$PlayInMode' => $row_options['play_in_mode'],
      '$PlayOutMode' => $row_options['play_out_mode'],
    );
  }


  // Arrow navigator.
  if ($options['global']['arrownavigator'] === 1) {
    $variables['arrownavigator'] = $options['global']['arrownavigator'];

    // Orientation to drag slide.
    $settings['$ArrowNavigatorOptions'] = array(
      '$Class' => '$JssorArrowNavigator$',
      '$AutoCenter' => isset($options['arrownavigator']['autocenter']) ? (int) $options['arrownavigator']['autocenter'] : 0,
      '$ChanceToShow' => isset($options['arrownavigator']['chancetoshow']) ? (int) $options['arrownavigator']['chancetoshow'] : 2,
      '$Steps' => isset($options['arrownavigator']['steps']) ? (int) $options['arrownavigator']['steps'] : 1,
      '$Scale' => (isset($options['arrownavigator']['scale']) && $options['arrownavigator']['scale'] === 0) ? FALSE : TRUE,
    );
  }

  // Bullet navigator.
  if ($options['global']['bulletnavigator'] === 1) {
    $variables['bulletnavigator'] = $options['global']['bulletnavigator'];

    // Orientation to drag slide.
    $settings['$BulletNavigatorOptions'] = array(
      '$Class' => '$JssorBulletNavigator$',
      '$ChanceToShow' => (int) $options['bulletnavigator']['chancetoshow'],
      '$ActionMode' => (int) $options['bulletnavigator']['action_mode'],
      '$AutoCenter' =>  (int) $options['bulletnavigator']['autocenter'],
      '$Steps' => (int) $options['bulletnavigator']['steps'],
      '$Rows' => (int) $options['bulletnavigator']['rows'],
      '$SpacingX' => (int) $options['bulletnavigator']['spacingx'],
      '$SpacingY' => (int) $options['bulletnavigator']['spacingy'],
      '$Orientation' => (int) $options['bulletnavigator']['orientation'],
      '$Scale' => (isset($options['bulletnavigator']['scale']) && $options['bulletnavigator']['scale'] === 0) ? FALSE : TRUE,
    );
  }

  // Attach settings.
  $view->element['#attached']['drupalSettings']['views']['jssorViews']['views_dom_id:' . $view->dom_id] = $settings;

  // Create a unique slider ID.
  $variables['slider_id'] = 'slider-dom-id-' . $view->dom_id;

  // Skins.
  $variables['arrowskin'] = $options['arrownavigator']['arrowskin'];
  $variables['bulletskin'] = $options['bulletnavigator']['bulletskin'];

  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : FALSE;
  foreach ($rows as $id => $row) {
    $variables['rows'][$id] = array();
    $variables['rows'][$id]['content'] = $row;
    $variables['rows'][$id]['attributes'] = array();

    if ($default_row_class) {
      $variables['rows'][$id]['attributes']['class'][] = 'views-row';
    }

    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $variables['rows'][$id]['attributes']['class'][] = $row_class;
    }

    $variables['rows'][$id]['attributes'] = new Attribute($variables['rows'][$id]['attributes']);
  }
}

/**
 * Prepares variables for views Jssor item templates.
 *
 * Default template: views-view-jssor-row.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - row: The raw results rows.
 */
function template_preprocess_views_view_jssor_row(&$variables) {
  $item = $variables['row'];
  $options = $variables['options'];

  $variables['caption'] = $item->caption;
  $variables['image'] = $item->image;

  $variables['play_in_transition'] = $options['play_in_transition'];
  $variables['play_out_transition'] = $options['play_out_transition'];
}

/**
 * Prepares variables for image jssor formatter templates.
 *
 * Default template: image-jssor-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - image_style: An optional image style.
 *   - url: An optional \Drupal\Core\Url object.
 */
function template_preprocess_image_jssor_formatter(&$variables) {
  if ($variables['image_style']) {
    $variables['image'] = array(
      '#theme' => 'image_style',
      '#style_name' => $variables['image_style'],
    );
  }
  else {
    $variables['image'] = array(
      '#theme' => 'image',
    );
  }
  $variables['image']['#attributes'] = $variables['item_attributes'];

  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (Unicode::strlen($item->title) != 0) {
    $variables['image']['#title'] = $item->title;
  }

  if (($entity = $item->entity) && empty($item->uri)) {
    $variables['image']['#uri'] = $entity->getFileUri();
  }
  else {
    $variables['image']['#uri'] = $item->uri;
  }

  foreach (array('width', 'height', 'alt') as $key) {
    $variables['image']["#$key"] = $item->$key;
  }

  $variables['caption_text'] = $item->alt;
}

/**
 * Prepares variables for container templates.
 *
 * Default template: images-jssor-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #attributes, #children.
 */
function template_preprocess_images_jssor_formatter(&$variables) {
  $element = $variables['element'];
  // Ensure #attributes is set.
  $element += array('#attributes' => array());

  $settings = $element['#settings'];

  $variables['arrownavigator'] = $settings['arrownavigator'];
  $variables['bulletnavigator'] = $settings['bulletnavigator'];

  $variables['children'] = $element['#children'];
  $variables['attributes'] = $element['#attributes'];
}
