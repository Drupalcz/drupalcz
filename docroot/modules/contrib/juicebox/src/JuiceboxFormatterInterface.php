<?php

/**
 * @file
 * Interface definition for a Juicebox Formatter service.
 */

namespace Drupal\juicebox;

use Drupal\file\FileInterface;

/**
 * Interface definition for a Juicebox Formatter service.
 */
interface JuiceboxFormatterInterface {

  /**
   * Create and initialize a new Juicebox gallery object.
   *
   * @param array $id_args
   *   An indexed array of simple string arguments that describe this gallery.
   *   This is typically based on the arguments that will be used to create a
   *   URL for the gallery XML, but no formal structure is strictly required.
   *   This information should uniquely identify the gallery.
   * @return Drupal\juicebox\JuiceboxGalleryInterface
   *   An initialized Juicebox gallery object.
   */
  public function newGallery($id_args);

  /**
   * Getter method for common global settings.
   *
   * @return array
   *   Returns an associative array of global gallery settings.
   */
  public function getGlobalSettings();

  /**
   * Get/detect the details of a Juicebox javascript library without loading it.
   *
   * This is essentially a wrapper for libraries_detect() with some caching
   * added. It also allows library info to be fetched independently from the
   * currently loaded version if needed (e.g., to accomodate XML requests that
   * don't come from this site).
   *
   * @param boolean $force_local
   *   Whether-or-not to force detection of the LOCALLY installed Juicebox
   *   library details. If FALSE Libraries API detection may be bypased if
   *   library version details can be detected through the URL.
   * @param boolean $reset
   *   Whether-or-not to bypass and reset any caching information.
   * @return array
   *   An associative array of the library information.
   */
  public function getLibrary($force_local = FALSE, $reset = FALSE);

  /**
   * Common post-build tasks that should take place whenever a gallery of any
   * type/source is built.
   *
   * @param Drupal\juicebox\JuiceboxGalleryInterface $gallery
   *   An initialized Juicebox gallery object.
   * @param $settings
   *   An associative array of common gallery-specific settings.
   * @param mixed $data
   *   Drupal source data that was used to build the gallery. This is included
   *   purely for reference.
   */
  public function runCommonBuild(JuiceboxGalleryInterface $gallery, $settings, $data = NULL);

  /**
   * Utility to extract image source data in an array structure that can be
   * used when adding a new image to the gallery.
   *
   * @param Drupal\file\FileInterface $image_file
   *   A file entity representing the main image.
   * @param string $image_style
   *   The Drupal image style to apply to the main image.
   * @param Drupal\file\FileInterface $thumb_file
   *   A file entity representing the thumbnail image.
   * @param string $thumb_style
   *   The Drupal image style to apply to the thumbnail image.
   * @param $settings
   *   An associative array of gallery-specific settings.
   * @return array
   *   An associative array of image source URLs that's ready to be added
   *   to a Juicebox gallery, including:
   *   - imageURL: URL to the full image to display.
   *   - thumbURL: URL to the thumbnail to display for the image.
   *   - linkURL: The Juicebox "link URL" value for the image.
   *   - linkTarget: The browser target value to use when following a link URL.
   *   - juicebox_compatible: Boolean indicating if the raw source file for the
   *     main image is directly compatible with the Juicebox library.
   */
  public function styleImageSrcData(FileInterface $image_file, $image_style, FileInterface $thumb_file, $thumb_style, $settings);

  /**
   * Build a render array for the embed code of a Juicebox gallery after images
   * and options have been added.
   *
   * Note that this is different from
   * Drupal\juicebox\JuiceboxGalleryInterface:renderEmbed() in that it handles
   * ALL considerations for embedding. This includes the addition of the
   * appropriate js and css which would otherwise need to be done independent of
   * renderEmbed(). It also uses the Drupal theme system as opposed to just
   * returning direct markup. Within Drupal this method should always be used.
   *
   * @param Drupal\juicebox\JuiceboxGalleryInterface $gallery
   *   An fully populated Juicebox gallery object.
   * @param $settings
   *   An associative array of gallery-specific settings.
   * @param array $xml_route_info
   *   Associative array of routing info that can be used to generate the URL to
   *   the XML. Includes:
   *   - route_name: The route name for the gallery XML.
   *   - route_parameters: Route parameters for the gallery XML.
   *   - options: An optional associative array of options that can be used by
   *     Drupal URL methods like Drupal\Core\Routing::generateFromRoute().
   * @param boolean $add_js
   *   Whether-or-not to add the Juicebox library and gallery-specific
   *   javascript.
   * @param boolean $add_xml
   *   It may be difficult or impossible to rebuild some types of formatters
   *   during a separate XML request, so this option offers a way around that by
   *   embedding the XML for the gallery directly into the HTML output. This
   *   XML can then be fetched from a request to this same page later via a
   *   sub-request. If TRUE xml-source-path and xml-source-id query strings are
   *   also added to the XML URL to help the XML building logic locate this XML
   *   data later. Setting this option may work around certain limitations but
   *   will likely lead to slower XML generation.
   * @param array $contextual
   *   Optional contextual link information that may be used in the display.
   *   This array will be added as-is to the #contextual-links part of the
   *   render array that's used for the gallery's embed code.
   * @return array
   *   Drupal render array for the embed code that describes a gallery.
   */
  public function buildEmbed(JuiceboxGalleryInterface $gallery, $settings, $xml_route_info, $add_js = TRUE, $add_xml = FALSE, $contextual = array());

  /**
   * Get the "base" values of common Drupal settings used to describe a gallery.
   * This is used for the management of default configuration values.
   *
   * @return array
   *   An associative array of base/default configuration values.
   */
  public function confBaseOptions();

  /**
   * Get common elements for Juicebox configuration forms.
   *
   * Several Juicebox gallery types can share common options and structures.
   * These can be merged into the appropriate forms via a call to this method.
   *
   * @param array $form
   *   The Drupal form array that common elements should be added to.
   * @param array $settings
   *   An associative array containing all the current settings for a Juicebox
   *   gallery (used to set default values).
   * @return array
   *   The common form elements merged within a form array.
   */
  public function confBaseForm($form, $settings);

  /**
   * Get the image style preset options that should be available in
   * configuration style picklists.
   *
   * This is in may ways just a wrapper for image_style_options() that allows
   * the addition of specical options that only Juicebox understands (e.g.
   * "multi-size").
   *
   * @param boolean $allow_multisize
   *   Whether-or-not to allow the addition of a PRO "multi-size" option. This
   *   is only included if this option is TRUE and the currently detected
   *   library is compatible with multi-size features.
   * @return array
   *   An associative array of style presets.
   */
  public function confBaseStylePresets($allow_multisize = TRUE);

}
