<?php

/**
 * @file
 * A php-only set of methods to create the script and markup components of a
 * Juicebox gallery.
 */

namespace Drupal\juicebox;

use \DOMDocument;

/**
 * Class to generate the script and markup for a Juicebox gallery.
 */
class JuiceboxGallery implements JuiceboxGalleryInterface {

  /**
   * Base properies to contain structured gallery data.
   */
  protected $id = '';
  // The settings property is specific to the logic used in this object.
  protected $settings = array();
  // The options and images properties are used to store configuration and
  // image data that will be processed into XML for use by the Juicebox
  // javascript library.
  protected $options = array();
  protected $images = array();

  /**
   * Constructor
   *
   * @param string $id
   *   A unique string id that can represent this gallery.
   * @param array $settings
   *   Configuration data to be used by this gallery object. These options
   *   relate to any "output" processing applied to the raw gallery variables
   *   upon rendering.
   *   - filter_markup: Optional boolean indicating whether-or-not to filter all
   *     image title and caption values for syntactic compatibility with
   *     Juicebox. This designates a gallery-wide default and can still be
   *     overriden on an image-by-image basis. Defaults to TRUE.
   *   - process_attributes: Optional boolean to activate legacy attribute name
   *     support for underscore-separated and dash-separated attributes
   *     (e.g., convert image_url to imageURL). Defaults to FALSE.
   */
  public function __construct($id = '', $settings = array()) {
    // Set the ID.
    $this->id = $id;
    // Ensure some default settings are specified.
    $settings += array(
      'filter_markup' => TRUE,
      'process_attributes' => FALSE,
    );
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($value, $reset = TRUE) {
    if ($reset || empty($this->id)) {
      $this->id = $value;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function addImage($src_data = array(), $title = '', $caption = '', $filter_markup = NULL, $override_id = NULL, $offset = NULL) {
    // If we are anticipating an override, but there is nothing to override,
    // don't do anything. Also, override_id and offset are mutually exclusive.
    if (isset($override_id) && (empty($this->images[$override_id]) || isset($offset))) {
      return FALSE;
    }
    // Make sure we have sufficient image data to work with. If legacy attribute
    // names are supported in the configuration then some names may be coming in
    // different formats (image_url vs. imageURL). In this case we need a
    // canonical form for validation.
    $src_data_canonical = !empty($this->settings['process_attributes']) ? $this->processAttributes($src_data) : $src_data;
    if (!isset($src_data_canonical['imageURL']) || !isset($src_data_canonical['thumbURL'])) {
      return FALSE;
    }
    // Add image to gallery, overriding if necessary.
    $addition = array(
      'src_data' => $src_data,
      'title' => $title,
      'caption' => $caption,
      'filter_markup' => $filter_markup,
    );
    if (isset($override_id)) {
      $this->images[$override_id] = $addition;
    }
    elseif (isset($offset)) {
      array_splice($this->images, $offset, 0, array($addition));
    }
    else {
      $this->images[] = $addition;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function updateImage($image_id, $src_data = array(), $title = '', $caption = '', $filter = TRUE) {
    // Updating can be accomplished with addImage(), so just pass-through the
    // needed params.
    return $this->addImage($src_data, $title, $caption, $filter, $image_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getImages($filtered = FALSE) {
    $images = $this->images;
    // If we are not returning the raw input data we need to apply any output
    // processing that may be specified in the object configuration.
    if ($filtered) {
      foreach ($images as &$image) {
        $image['title'] = !empty($this->settings['filter_markup']) ? $this->filterMarkup($image['title']) : $image['title'];
        $image['caption'] = !empty($this->settings['filter_markup']) ? $this->filterMarkup($image['caption']) : $image['caption'];
        $image['src_data'] = !empty($this->settings['process_attributes']) ? $this->processAttributes($image['src_data']) : $image['src_data'];
        // Also derive linkURL and linkTarget values if they are not explicitly
        // set.
        if (!isset($image['src_data']['linkURL'])) {
          $image['src_data']['linkURL'] = $image['src_data']['imageURL'];
        }
        if (!isset($image['src_data']['linkTarget'])) {
          $image['src_data']['linkTarget'] = '_blank';
        }
      }
    }
    return $images;
  }

  /**
   * {@inheritdoc}
   */
  public function removeImage($id) {
    if (!empty($this->images[$id])) {
      unset($this->images[$id]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addOption($option_name, $option_value, $override = TRUE) {
    // Always use lowercase keys to allow for future lookups.
    $option_name = strtolower($option_name);
    if (!empty($this->options[$option_name]) && !$override) {
      return FALSE;
    }
    // Add option,
    $this->options[$option_name] = $option_value;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($filtered = FALSE) {
    $options = $this->options;
    // If we are not returning the raw input data we need to apply any output
    // processing that may be specified in the object configuration.
    if ($filtered) {
      $options = !empty($this->settings['process_attributes']) ? $this->processAttributes($options) : $options;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function removeOption($option_name) {
    $option_name = strtolower($option_name);
    if (!empty($this->options[$option_name])) {
      unset($this->options[$option_name]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getChecksum() {
    return md5(json_encode($this->images) . json_encode($this->options));
  }

  /**
   * {@inheritdoc}
   */
  public function renderXml($embed_wrap_id = NULL) {
    // We use DOMDocument instead of a SimpleXMLElement to build the XML as it's
    // much more flexible (CDATA is supported, etc.).
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = TRUE;
    $juicebox = $dom->appendChild($dom->createElement('juicebox'));
    // Get filtered attributes.
    $gallery_attributes = $this->getOptions(TRUE);
    foreach ($gallery_attributes as $attribute => $value) {
      $juicebox->setAttribute($attribute, $value);
    }
    // Get filtered image data.
    $gallery_images = $this->getImages(TRUE);
    foreach ($gallery_images as $image) {
      $juicebox_image = $juicebox->appendChild($dom->createElement('image'));
      foreach ($image['src_data'] as $attribute => $value) {
        $juicebox_image->setAttribute($attribute, $value);
      }
      $juicebox_image_title = $juicebox_image->appendChild($dom->createElement('title'));
      $juicebox_image_title->appendChild($dom->createCDATASection($image['title']));
      $juicebox_image_caption = $juicebox_image->appendChild($dom->createElement('caption'));
      $juicebox_image_caption->appendChild($dom->createCDATASection($image['caption']));
    }
    $prefix = $suffix = '';
    if ($embed_wrap_id) {
      $prefix = '<script id="' . $embed_wrap_id . '" type="text/xml">';
      $suffix = '</script>';
    }
    return $prefix . $dom->saveXML() . $suffix;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbed() {
    $output = '';
    $output .= '<div class="juicebox-parent">';
    $output .= '<div id="' . $this->id . '" class="juicebox-container">';
    $output .= '</div></div>';
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function renderJavascript($xml_url, $add_script_tags = FALSE, $jquery_defer = FALSE) {
    // Get variable inputs for the juicebox object and represent them as a
    // string.
    $vars_json = json_encode($this->getJavascriptVars($xml_url));
    // Set the Juicebox-specific code.
    $output = "new juicebox(" . $vars_json . ");";
    // Set wrappers.
    $script_wrap_prefix = $script_wrap_suffix = $inner_wrap_prefix = $inner_wrap_suffix = '';
    if ($add_script_tags) {
      $script_wrap_prefix = '<script type="text/javascript">' . "\n<!--//--><![CDATA[//><!--\n";
      $script_wrap_suffix = "\n//--><!]]>\n" . '</script>';
    }
    if ($jquery_defer) {
      $inner_wrap_prefix = 'jQuery(document).ready(function () {';
      $inner_wrap_suffix = '});';
    }
    return "\n" . $script_wrap_prefix . $inner_wrap_prefix . $output . $inner_wrap_suffix . $script_wrap_suffix . "\n";
  }

  /**
   * {@inheritdoc}
   */
  public function getJavascriptVars($xml_url) {
    $vars = array(
      'configUrl' => $xml_url,
      'containerId' => $this->getId(),
    );
    // Add options that need to be loaded immediately (before XML is available).
    $load_before_xml = array('gallerywidth', 'galleryheight', 'backgroundcolor', 'themeurl', 'baseurl', 'showpreloader', 'debugmode');
    $current_options = $this->getOptions(TRUE);
    foreach ($load_before_xml as $key => $option) {
      if (!empty($current_options[$option])) {
        $vars[$option] = $current_options[$option];
      }
    }
    return $vars;
  }

  /**
   * Process an attribute list for valid use in a Juicebox gallery.
   *
   * For legacy support we can allow a variety of attribute name inputs,
   * including underscore-separated and dash-separated values. Because the
   * Juicebox library expects attribute names in camelCase we use this method to
   * make a best-effort conversion (e.g., convert image_url to imageURL). Note
   * that this process does NOT do any sanitization and can only correctly
   * convert certain input names.
   *
   * @param array $attributes
   *   An associative array of name => value pairs for juicebox XML attributes
   *   to be converted.
   * @return array
   *   The converted array of attributes.
   */
  protected function processAttributes($attributes) {
    $filtered = array();
    foreach ($attributes as $name => $value) {
      // First make some adjustments for legacy support. We used to use some
      // specialized keys that are no longer valid, but still want to support
      // them as input. These values are special and cannot be handled alone by
      // the word-separator processing logic below.
      $name_mappings = array('image_url_small' => 'smallImageURL', 'image_url_large' => 'largeImageURL');
      if (array_key_exists($name, $name_mappings)) {
        $name = $name_mappings[$name];
      }
      // Check that the name is in a format expected by the XML such that any
      // word-separators (like "-" and "_") are dropped. Also, the Juicebox
      // library uses camelCase for attributes so we do our best to deliver
      // that. Note that for image attributes proper camelCase appears to be
      // REQUIRED, but for gallery attributes all-lowercase values will work in
      // addition to camelCase.
      $parts = preg_split('/(_|-)/', $name);
      $i = 0;
      foreach ($parts as &$word) {
        if ($i) { // Don't alter the first word.
          $word = ucfirst($word);
          // For some reason the library requires that some attributes
          // containing a "url" string capitalize the "url" part.
          $word = $word == 'Url' ? 'URL' : $word;
        }
        $i++;
      }
      $name = implode('', $parts);
      $filtered[$name] = $value;
    }
    return $filtered;
  }

  /**
   * Filter markup for valid display in a Juicebox gallery.
   *
   * Some markup that validates fine via external filters will not be
   * syntactically valid once rendered within Juicebox. This is because Juicebox
   * will wrap titles and captions in block-level tags, like <p>, making any
   * block-level elements they contain invalid. This filter accommodates for
   * this and is meant to be applied AFTER any external filters. Note that this
   * process does NOT do any sanitization.
   *
   * @param string $markup
   *   The markup to be filtered after it has been processed externally.
   * @return string
   *   Valid filtered markup ready for display in a Juicebox gallery.
   */
  protected function filterMarkup($markup) {
    // Set inline html5 elements that are safe in a Juicebox gallery. Ref:
    // http://www.w3.org/html/wg/drafts/html/master/single-page.html#phrasing-content
    $valid_elements = "<a><abbr><area><audio><b><bdi><bdo><br><button><canvas><cite><code><data><del><dfn><em><embed><i><iframe><img><input><ins><kbd><keygen><label><link><map><mark><math><meta><meter><noscript><object><output><progress><q><ruby><s><samp><script><select><small><span><strong><sub><sup><svg><template><textarea><time><u><var><video><wbr>";
    // Add some html4 additions for legacy support.
    // Ref: http://www.htmlhelp.com/reference/html40/inline.html
    $valid_elements .= "<acronym><basefont><big><font><rp><rt><strike><tt>";
    $markup = strip_tags($markup, $valid_elements);
    // Also remove newlines to keep the output concise.
    $markup = str_replace(array("\r", "\n"), '', $markup);
    return $markup;
  }

}
