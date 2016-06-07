<?php

/**
 * @file
 * Test case for Juicebox field formatter.
 */

namespace Drupal\juicebox\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;

/**
 * Tests integration with Views module.
 *
 * @group Juicebox
 */
class JuiceboxViewsCase extends JuiceboxBaseCase {

  public static $modules = array('node', 'text', 'field', 'image', 'editor', 'juicebox', 'views', 'contextual', 'juicebox_mimic_article', 'juicebox_test_views');
  protected $instBundle = 'article';
  protected $instFieldName = 'field_image';
  // Uncomment the line below, and remove juicebox_mimic_article from the module
  // list above, to use the "standard" profile's article type for this test
  // instead of the one we create manually (should also work, but will be slow).
  // protected $profile = 'standard';

  /**
   * Define setup tasks.
   */
  public function setUp() {
    parent::setUp();

    // Create and login user.
    $this->webUser = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer nodes', 'bypass node access', 'access contextual links', 'use text format basic_html'));
    $this->drupalLogin($this->webUser);
    // Create a test node. Note that we don't need to initiate a node and field
    // structure before this because that's been handled for us by
    // juicebox_mimic_article.
    $this->createNodeWithFile('image', FALSE, FALSE);
    // Start all cases as an anon user.
    $this->drupalLogout();
  }

  /**
   * Test using pre-packaged base Juicebox view.
   */
  public function testViews() {
    $node = $this->node;
    $xml_path = 'juicebox/xml/viewsstyle/juicebox_views_test/page_1';
    $xml_url = \Drupal::url('juicebox.xml_viewsstyle', array('viewName' => 'juicebox_views_test', 'displayName' => 'page_1'));
    // Get the urls to the test image and thumb derivative used by default.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    $test_thumb_url = entity_load('image_style', 'juicebox_square_thumb')->buildUrl($uri);
    // Check for correct embed markup.
    $this->drupalGet('juicebox-views-test');
    $this->assertRaw(trim(json_encode(array('configUrl' => $xml_url)), '{}"'), 'Gallery setting found in Drupal.settings.');
    $this->assertRaw('juicebox-views-test--page-1', 'Embed code wrapper found.');
    $this->assertRaw(Html::escape(file_url_transform_relative($test_image_url)), 'Test image found in embed code');
    // Check for correct XML.
    $this->drupalGet($xml_path);
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'Valid XML detected.');
    $this->assertRaw('imageURL="' . Html::escape($test_image_url), 'Test image found in XML.');
    $this->assertRaw('thumbURL="' . Html::escape($test_thumb_url), 'Test thumbnail found in XML.');
    // A test leveraging fields with formattiong options also allows us to test
    // as additional global justboc configuration - the markup filter. The
    // node's title and body are mapped to the image title and caption. Most
    // formatting should be maintained in the caption because it's based on
    // a body field (with default settings to use "basic_html" text format).
    // However that our Juicebox markup filter should have stripped the
    // block-level p tags that were *added* by default by the text format.
    $this->assertRaw('<title><![CDATA[' . $this->node->getTitle() . ']]></title>', 'Image title text found in XML.');
    $this->assertRaw('<caption><![CDATA[Some body content on node ' . $this->node->id() . ' <strong>with formatting</strong>]]></caption>', 'Image caption text found in XML.');
    // Check for contextual links in embed code. It might we worth checking if
    // there is a more programmatic way to build the related id at some point.
    $this->drupalLogin($this->webUser); // Need access to contextual links.
    $this->drupalGet('juicebox-views-test');
    $id = 'juicebox_xml_viewsstyle:viewName=juicebox_views_test&displayName=page_1:langcode=en|juicebox_conf_global::langcode=en';
    $this->assertRaw('<div data-contextual-id="' . Html::escape($id) . '"></div>', 'Correct contextual link placeholders found.');
    $json = Json::decode($this->renderContextualLinks(array($id), 'juicebox-views-test'));
    $this->assertResponse(200);
    $this->assertTrue(preg_match('|/' . $xml_path . '.*/admin/config/media/juicebox|', $json[$id]), 'Correct contextual links found.');
    // Also test the toggle for the Juicebox markup filter.
        $edit = array(
      'apply_markup_filter' => FALSE,
    );
    $this->drupalPostForm('admin/config/media/juicebox', $edit, t('Save configuration'));
    $this->assertText(t('The Juicebox configuration options have been saved'), 'Custom global options saved.');
    // Logout and test the formatting changge.
    $this->drupalLogout();
    $this->drupalGet($xml_path);
    // With the filter off, the p tags in our example caption should come
    // through.
    $this->assertRaw('<caption><![CDATA[<p>Some body content on node ' . $this->node->id() . ' <strong>with formatting</strong></p>', 'Image caption text found in XML.');
  }

  /**
   * Test using pre-packaged advanced Juicebox view.
   *
   * The view tested here is largely the same as the "base" one tested above
   * but it includes tight access restrictions relationships.
   */
  public function testViewsAdvanced() {
    // Start as an real user as
    $this->drupalLogin($this->webUser);
    $node = $this->node;
    $xml_path = 'juicebox/xml/viewsstyle/juicebox_views_test/page_2';
    $xml_url = \Drupal::url('juicebox.xml_viewsstyle', array('viewName' => 'juicebox_views_test', 'displayName' => 'page_2'));
    // Get the urls to the test image and thumb derivative used by default.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    $test_thumb_url = entity_load('image_style', 'juicebox_square_thumb')->buildUrl($uri);
    // Check for correct embed markup.
    $this->drupalGet('juicebox-views-test-advanced');
    $this->assertRaw(trim(json_encode(array('configUrl' => $xml_url)), '{}"'), 'Gallery setting found in Drupal.settings.');
    $this->assertRaw('juicebox-views-test--page-2', 'Embed code wrapper found.');
    $this->assertRaw(Html::escape(file_url_transform_relative($test_image_url)), 'Test image found in embed code');
    // Check for correct XML.
    $this->drupalGet($xml_path);
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'Valid XML detected.');
    $this->assertRaw('imageURL="' . Html::escape($test_image_url), 'Test image found in XML.');
    $this->assertRaw('thumbURL="' . Html::escape($test_thumb_url), 'Test thumbnail found in XML.');
    // Logout and test that XML access is restricted. Note that this test view
    // is setup to limit view access only to admins.
    $this->drupalLogout();
    $this->drupalGet('juicebox/xml/viewsstyle/juicebox_views_test/page_2');
    $this->assertResponse(403, 'XML access blocked for access-restricted view.');
  }

  /**
   * Test using pre-packaged Juicebox view that lists files instead of content.
   */
  public function testViewsFile() {
    $node = $this->node;
    $xml_path = 'juicebox/xml/viewsstyle/juicebox_views_files_test/page_1';
    $xml_url = \Drupal::url('juicebox.xml_viewsstyle', array('viewName' => 'juicebox_views_files_test', 'displayName' => 'page_1'));
    // Get the urls to the test image and thumb derivative used by default.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    $test_thumb_url = entity_load('image_style', 'juicebox_square_thumb')->buildUrl($uri);
    // Check for correct embed markup.
    $this->drupalGet('juicebox-views-files-test');
    $this->assertRaw(trim(json_encode(array('configUrl' => $xml_url)), '{}"'), 'Gallery setting found in Drupal.settings.');
    $this->assertRaw('juicebox-views-files-test--page-1', 'Embed code wrapper found.');
    $this->assertRaw(Html::escape(file_url_transform_relative($test_image_url)), 'Test image found in embed code');
    // Check for correct XML.
    $this->drupalGet($xml_path);
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'Valid XML detected.');
    $this->assertRaw('imageURL="' . Html::escape($test_image_url), 'Test image found in XML.');
    $this->assertRaw('thumbURL="' . Html::escape($test_thumb_url), 'Test thumbnail found in XML.');
    // Check that the file mimetype is used as the title.
    $filename = $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getMimeType();
    $this->assertRaw('<title><![CDATA[' . $filename . ']]></title>', 'Image title text found in XML.');
  }

}
