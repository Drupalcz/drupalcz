<?php

/**
 * @file
 * Test case for Juicebox file handling.
 */

namespace Drupal\juicebox\Tests;

use Drupal\Component\Utility\Html;


/**
 * Tests general file and non-image handling.
 *
 * @group Juicebox
 */
class JuiceboxFileCase extends JuiceboxBaseCase {

  public static $modules = array('node', 'field_ui', 'image', 'juicebox');
  protected $instFieldName = 'field_file';
  public $instFieldType = 'file';


  /**
   * Define setup tasks.
   */
  public function setUp() {
    parent::setUp();
    // Create and login user.
    $this->webUser = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer nodes', 'administer node fields', 'administer node display', 'bypass node access'));
    $this->drupalLogin($this->webUser);
    // Prep a node with an image/file field and create a test entity.
    $this->initNode();
    // Activte the field formatter for our new node instance.
    $this->activateJuiceboxFieldFormatter();
  }

  /**
   * Test the field formatter with a file field and file upload widget.
   */
  public function testFile() {
    // Create a test node with an image file.
    $this->createNodeWithFile();
    $node = $this->node;
    $xml_path = 'juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full';
    $xml_url = \Drupal::url('juicebox.xml_field', array('entityType' => 'node', 'entityId' => $node->id(), 'fieldName' => $this->instFieldName, 'displayName' => 'full'));
    // Get the urls to the test image and thumb derivative used by default.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    $test_thumb_url = entity_load('image_style', 'juicebox_square_thumb')->buildUrl($uri);
    // Check for correct embed markup as anon user.
    $this->drupalLogout();
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw(trim(json_encode(array('configUrl' => $xml_url)), '{}"'), 'Gallery setting found in Drupal.settings.');
    $this->assertRaw('id="node--' . $node->id() . '--' . str_replace('_', '-', $this->instFieldName) . '--full"', 'Embed code wrapper found.');
    $this->assertRaw(Html::escape(file_url_transform_relative($test_image_url)), 'Test image found in embed code');
    // Check for correct XML.
    $this->drupalGet($xml_path);
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'Valid XML detected.');
    $this->assertRaw('imageURL="' . Html::escape($test_image_url), 'Test image found in XML.');
    $this->assertRaw('thumbURL="' . Html::escape($test_thumb_url), 'Test thumbnail found in XML.');
    $this->assertRaw('<juicebox gallerywidth="100%" galleryheight="100%" backgroundcolor="#222222" textcolor="rgba(255,255,255,1)" thumbframecolor="rgba(255,255,255,.5)" showopenbutton="TRUE" showexpandbutton="TRUE" showthumbsbutton="TRUE" usethumbdots="FALSE" usefullscreenexpand="FALSE">', 'Expected default configuration options set in XML.');
  }

  /**
   * Test the non-image handling feature.
   */
  public function testFileNonImage() {
    // Create a test node with a non-image file.
    $this->createNodeWithFile('text');
    $node = $this->node;
    // Check the XML as anon user.
    $this->drupalLogout();
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    // With the default settings we expect an "application-octet-stream.png"
    // value for both the image and the thumbnail.
    $this->assertPattern('|imageURL=.*text.png.*thumbURL=.*text.png|', 'Non-image mimetype placeholder found for image and thumbnail.');
    // Change the file handling option to "skip".
    $this->drupalLogin($this->webUser);
    $this->drupalPostAjaxForm('admin/structure/types/manage/' . $this->instBundle . '/display', array(), $this->instFieldName . '_settings_edit', NULL, array(), array(), 'entity-view-display-edit-form');
    $edit = array(
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][incompatible_file_action]' => 'skip',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your settings have been saved.'), 'Gallery configuration changes saved.');
    // Re-check the XML. This time no image should appear at all.
    $this->drupalLogout();
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'Valid XML detected.');
    $this->assertNoRaw('<image', 'Non-image items was skipped.');
    // @todo, Check other incompatible_file_action combinations.
  }

}
