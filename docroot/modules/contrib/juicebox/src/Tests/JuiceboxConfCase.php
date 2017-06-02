<?php

/**
 * @file
 * Test case for gallery-specific Juicebox configuration options.
 */

namespace Drupal\juicebox\Tests;

use Drupal\Component\Utility\Html;


/**
 * Tests gallery-specific configuration logic for Juicebox galleries.
 *
 * @group Juicebox
 */
class JuiceboxConfCase extends JuiceboxBaseCase {

  public static $modules = array('node', 'field_ui', 'image', 'juicebox');


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
    // Create a test node.
    $this->createNodeWithFile();
    // Start all cases as an anon user.
    $this->drupalLogout();
  }

  /**
   * Test common Lite configuration logic for a Juicebox formatter.
   */
  public function testConfigLite() {
    $node = $this->node;
    // Check control case as anon user without custom configuration. This will
    // also prime the cache in order to test cache tag invalidation once the
    // settings are altered.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw(trim(json_encode(array('gallerywidth' => '100%', 'galleryheight' => '100%', 'backgroundcolor' => '#222222')), '{}'), 'Expected default configuration options found in Drupal.settings.');
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('<juicebox gallerywidth="100%" galleryheight="100%" backgroundcolor="#222222" textcolor="rgba(255,255,255,1)" thumbframecolor="rgba(255,255,255,.5)" showopenbutton="TRUE" showexpandbutton="TRUE" showthumbsbutton="TRUE" usethumbdots="FALSE" usefullscreenexpand="FALSE">', 'Expected default configuration options set in XML.');
    // Alter settings to contain custom values.
    $this->drupalLogin($this->webUser);
    $this->drupalPostAjaxForm('admin/structure/types/manage/' . $this->instBundle . '/display', array(), $this->instFieldName . '_settings_edit', NULL, array(), array(), 'entity-view-display-edit-form');
    $edit = array(
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_galleryWidth]' => '50%',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_galleryHeight]' => '200px',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_backgroundColor]' => 'red',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_textColor]' => 'green',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_thumbFrameColor]' => 'blue',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_showOpenButton]' => FALSE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_showExpandButton]' => FALSE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_showThumbsButton]' => FALSE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_useThumbDots]' => TRUE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_useFullscreenExpand]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your settings have been saved.'), 'Gallery configuration changes saved.');
    // Now check the resulting XML again as an anon user.
    $this->drupalLogout();
    // Check for correct embed markup.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw(trim(json_encode(array('gallerywidth' => '50%', 'galleryheight' => '200px', 'backgroundcolor' => 'red')), '{}'), 'Expected custom Lite configuration options found in Drupal.settings.');
    // Check for correct XML.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('<juicebox gallerywidth="50%" galleryheight="200px" backgroundcolor="red" textcolor="green" thumbframecolor="blue" showopenbutton="FALSE" showexpandbutton="FALSE" showthumbsbutton="FALSE" usethumbdots="TRUE" usefullscreenexpand="TRUE">', 'Expected custom Lite configuration options set in XML.');
  }

  /**
   * Test common Pro configuration logic for a Juicebox formatter.
   */
  public function testConfigPro() {
    $node = $this->node;
    // Do a set of control requests as an anon user that will also prime any
    // caches.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200, 'Control request of test node was successful.');
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertResponse(200, 'Control request of XML was successful.');
    // Set new manual options and also add a manual customization that's
    // intended to override a custom Lite option.
    $this->drupalLogin($this->webUser);
    $this->drupalPostAjaxForm('admin/structure/types/manage/' . $this->instBundle . '/display', array(), $this->instFieldName . '_settings_edit', NULL, array(), array(), 'entity-view-display-edit-form');
    $edit = array(
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_showExpandButton]' => FALSE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][manual_config]' => "sHoWoPeNbUtToN=\"FALSE\"\nshowexpandbutton=\"TRUE\"\ngallerywidth=\"50%\"\nmyCustomSetting=\"boomsauce\"",
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your settings have been saved.'), 'Gallery configuration changes saved.');
    $this->drupalLogout();
    // Check for correct embed markup.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw(trim(json_encode(array('gallerywidth' => '50%', 'galleryheight' => '100%', 'backgroundcolor' => '#222222')), '{}'), 'Expected custom configuration options found in Drupal.settings.');
    // Check for correct XML.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('<juicebox gallerywidth="50%" galleryheight="100%" backgroundcolor="#222222" textcolor="rgba(255,255,255,1)" thumbframecolor="rgba(255,255,255,.5)" showopenbutton="FALSE" showexpandbutton="TRUE" showthumbsbutton="TRUE" usethumbdots="FALSE" usefullscreenexpand="FALSE" mycustomsetting="boomsauce">', 'Expected custom Pro configuration options set in XML.');
  }

  /**
   * Test common Advanced configuration logic for a Juicebox formatter.
   */
  public function testConfigAdvanced() {
    $node = $this->node;
    // Get the urls to the main image with and without "large" styling.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = file_create_url($uri);
    $test_image_url_formatted = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    // Check control case without custom configuration.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('linkTarget="_blank"', 'Default linkTarget setting found.');
    $this->assertRaw('linkURL="' . $test_image_url, 'Test unstyled image found in XML');
    // Set new advanced options.
    $this->drupalLogin($this->webUser);
    $this->drupalPostAjaxForm('admin/structure/types/manage/' . $this->instBundle . '/display', array(), $this->instFieldName . '_settings_edit', NULL, array(), array(), 'entity-view-display-edit-form');
    $edit = array(
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][image_style]' => 'juicebox_medium',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][linkurl_source]' => 'image_styled',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][linkurl_target]' => '_self',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][custom_parent_classes]' => 'my-custom-wrapper',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your settings have been saved.'), 'Gallery configuration changes saved.');
    $this->drupalLogout();
    // Check case with custom configuration.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('linkTarget="_self"', 'Updated linkTarget setting found in XML.');
    $this->assertRaw('linkURL="' . Html::escape($test_image_url_formatted), 'Test styled image found in XML for linkURL.');
    // Also check for custom class in embed code.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('class="juicebox-parent my-custom-wrapper"', 'Custom class found in embed code.');
  }

}
