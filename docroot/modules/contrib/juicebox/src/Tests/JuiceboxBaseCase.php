<?php

/**
 * @file
 * Common helper methods for Juicebox module tests.
 */

namespace Drupal\juicebox\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;


/**
 * Common helper class for Juicebox module tests.
 */
abstract class JuiceboxBaseCase extends WebTestBase {

  // Common variables.
  protected $webUser;
  // Properties to store details of the field that will be use in a field
  // formatter test.
  protected $node;
  protected $instBundle = 'juicebox_gallery';
  protected $instFieldName = 'field_juicebox_image';
  protected $instFieldType = 'image';


  /**
   * Setup a new content type, with a image/file field.
   */
  protected function initNode() {
    // Create a new content type.
    $this->drupalCreateContentType(array('type' => $this->instBundle, 'name' => $this->instBundle));
    // Prep a field base.
    $field_storage_settings = array(
      'display_field' => TRUE,
      'display_default' => TRUE,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );
    $field_storage = array(
      'entity_type' => 'node',
      'field_name' => $this->instFieldName,
      'type' => $this->instFieldType,
      'settings' => $field_storage_settings,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );
    entity_create('field_storage_config', $field_storage)->save();
    // Prep a field instance.
    $field_settings = array();
    if ($this->instFieldType == 'image') {
      $field_settings['alt_field'] = TRUE;
      $field_settings['alt_field_required'] = FALSE;
      $field_settings['title_field'] = TRUE;
      $field_settings['title_field_required'] = FALSE;
    }
    if ($this->instFieldType == 'file') {
      $field_settings['description_field'] = TRUE;
      $field_settings['file_extensions'] = 'txt jpg png mp3 rtf docx pdf';
    }
    $field = array(
      'field_name' => $this->instFieldName,
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundle' => $this->instBundle,
      'required' => FALSE,
      'settings' => $field_settings,
    );
    entity_create('field_config', $field)->save();
    // Setup widget.
    entity_get_form_display('node', $this->instBundle, 'default')
      ->setComponent($this->instFieldName, array(
        'type' => 'file_generic',
        'settings' => array(),
      ))
      ->save();
    // Clear some caches for good measure.
    $entity_manager = $this->container->get('entity.manager');
    $entity_manager->getStorage('field_storage_config')->resetCache();
    $entity_manager->getStorage('field_config')->resetCache();
  }

  /**
   * Helper to activate a Juicebox field formatter on a field.
   */
  protected function activateJuiceboxFieldFormatter() {
    entity_get_display('node', $this->instBundle, 'default')
      ->setComponent($this->instFieldName, array(
        'type' => 'juicebox_formatter',
        'settings' => array(),
      ))
      ->save();
  }

  /**
   * Helper to create a node and upload a file to it.
   */
  protected function createNodeWithFile($file_type = 'image', $multivalue = TRUE, $add_title_caption = TRUE) {
    $file = current($this->drupalGetTestFiles($file_type));
    $edit = array(
      'title[0][value]' => 'Test Juicebox Gallery Node',
      'files[' . $this->instFieldName . '_0]' . ($multivalue ? '[]' : '') => drupal_realpath($file->uri),
    );
    $this->drupalPostForm('node/add/' . $this->instBundle, $edit, t('Save and publish'));
    // Get ID of the newly created node from the current URL.
    $matches = array();
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    if (isset($matches[1])) {
      $nid = $matches[1];
      // Now re-edit the node to add title and caption values for the newly
      // uploaded image. This could probably also be done above with
      // DrupalWebTestCase::drupalPostAJAX(), but this works too.
      $edit = array(
        'body[0][value]' => 'Some body content on node ' . $nid . ' <strong>with formatting</strong>',
      );
      if ($add_title_caption) {
        if ($this->instFieldType == 'image') {
          $edit[$this->instFieldName . '[0][title]'] = 'Some title text for field ' . $this->instFieldName . ' on node ' . $nid;
          $edit[$this->instFieldName . '[0][alt]'] = 'Some alt text for field ' . $this->instFieldName . ' on node ' . $nid . ' <strong>with formatting</strong>';
        }
        if ($this->instFieldType == 'file') {
          $edit[$this->instFieldName . '[0][description]'] = 'Some description text for field ' . $this->instFieldName . ' on node ' . $nid . ' <strong>with formatting</strong>';
        }
      }
      $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save and keep published'));
      // Clear some caches for good measure and save the node object for
      // reference during tests.
      $node_storage = $this->container->get('entity.manager')->getStorage('node');
      $node_storage->resetCache(array($nid));
      $this->node = $node_storage->load($nid);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get server-rendered contextual links for the given contextual link ids.
   *
   * @param array $ids
   *   An array of contextual link ids.
   * @param string $current_path
   *   The Drupal path for the page for which the contextual links are rendered.
   *
   * @return string
   *   The response body.
   */
  protected function renderContextualLinks($ids, $current_path) {
    $post = array();
    for ($i = 0; $i < count($ids); $i++) {
      $post['ids[' . $i . ']'] = $ids[$i];
    }
    return $this->drupalPost('contextual/render', 'application/json', $post, array('query' => array('destination' => $current_path)));
  }

}
