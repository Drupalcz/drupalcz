<?php


/**
 * @file
 * Hooks provided by the Juicebox module.
 */


/**
 * Allow modules to alter the Juicebox gallery object used to build gallery
 * embed code and XML before rendering.
 *
 * @param object $gallery
 *   A Juicebox gallery object that contains the gallery which is going to be
 *   rendered. This object can be further manipulated using any methods from
 *   Drupal\juicebox\JuiceboxGalleryInterface.
 * @param mixed $data
 *   The raw Drupal data that was used to build this gallery. Provided for
 *   context.
 */
function hook_juicebox_gallery_alter($gallery, $data) {
  // Only make changes to galleries that use the field formatter
  if (strpos($gallery->getId(), 'field') === 0) {
    foreach ($gallery->getImages() as $key => $image) {
      // Add some static text to all title values and write changes back.
      $image['title'] .= ' &copy; 2014';
      $gallery->updateImage($key, $image['src_data'], $image['title'], $image['caption']);
    }
  }
}


/**
 * Allow modules to alter the class used to instantiate a Juicebox gallery.
 *
 * @param string $class
 *   The class to use (must implement Drupal\juicebox\JuiceboxGalleryInterface)
 *   when creating a new Juicebox gallery.
 * @param array $library
 *   Juicebox javascript library data as provided through Libraries API.
 *   Provided for context.
 */
function hook_juicebox_classes_alter(&$class, $library) {
  // Swap out the gallery dependency object because some future Juicebox
  // javascript library requires different embed or XML output.
  if (!empty($library['version']) && $library['version'] == 'Pro 12.3') {
    $class = 'FutureJuiceboxGallery';
  }
}
