<?php


/**
 * @file
 * Theme and template functions for the Juicebox module.
 */

use Drupal\Component\Utility\SafeMarkup;


/**
 * Preprocessor for juicebox_embed_markup theme definition.
 */
function juicebox_preprocess_juicebox_embed_markup(&$vars) {
  // Extract base data needed by the template from the galley object.
  $element = $vars['element'];
  $gallery = $element['#gallery'];
  $vars['gallery_id'] = $gallery->getId();
  $vars['gallery_images'] = $gallery->getImages(TRUE);
  $vars['gallery_options'] = $gallery->getOptions(TRUE);
  // Add the overall wrapper classes.
  $vars['attributes']['class'][] = 'juicebox-parent';
  if (!empty($element['#settings']['custom_parent_classes'])) {
    $vars['attributes']['class'][] = trim($element['#settings']['custom_parent_classes']);
  }
  // Calculate a "standard" image link render array for each image. This allows
  // the main template to add normal image markup within the embed code as
  // needed (e.g. inside noscript containers).
  foreach ($vars['gallery_images'] as &$image) {
    // Use the standard "image" template.
    $img_vars = array('#theme' => 'image');
    $img_vars['#uri'] = $image['src_data']['imageURL'];
    if (!empty($image['title'])) {
      // Our image title should already be sanatized by this point, and
      // therefore its content will have been marked "safe" for display without
      // further escapting. However, some additional markup stripping may have
      // happened in Drupal\juicebox\JuieboxGallery, so we need to re-mark the
      // current form as "safe".
      $image['title'] = SafeMarkup::format($image['title'], array());
      $img_vars['#title'] = $image['title'];
    }
    if (!empty($image['caption'])) {
      // Re-mark caption markup as "safe" as well.
      $image['caption'] = SafeMarkup::format($image['caption'], array());
      $img_vars['#alt'] = $image['caption'];
    }
    $image['image_plain'] = $img_vars;
  }
}
