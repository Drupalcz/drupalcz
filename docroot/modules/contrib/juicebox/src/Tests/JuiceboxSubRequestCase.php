<?php

/**
 * @file
 * Test case for Juicebox file handling.
 */

namespace Drupal\juicebox\Tests;

use Drupal\Component\Utility\Html;


/**
 * Tests the Juicebox XML generation via a sub-request.
 *
 * @group Juicebox
 */
class JuiceboxSubRequestCase extends JuiceboxBaseCase {

  // For some totally vexing reason, tests using views that contain file/image
  // fields won't work if the field configuration is created progrmatically as
  // part of the test itself. In other words if we use
  // JuiceboxBaseCase::initNode() to add fields and attach them to a node, and
  // then load a view conf that uses these fields, things won't work (the view
  // will have broken handlers). This much have somthing to do with the config
  // schema or something (progratically created entities are not yet recognized
  // when a view config that depends on them in loaded)? I have resourted to
  // loading the field conf within a helper module (juicebox_mimic_article) as
  // it seems to get around this. That module just mimics an "article" type so
  // another option is to use the "standard" profile and remove
  // juicebox_mimic_article below (but that's slow).
  public static $modules = array('node', 'text', 'field', 'image', 'editor', 'juicebox', 'views', 'juicebox_mimic_article', 'juicebox_test_views');
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
    $this->webUser = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($this->webUser);
    // Create a test node. Note that we don't need to initiate a node and field
    // structure before this because that's been handled for us by
    // juicebox_mimic_article.
    $this->createNodeWithFile('image', FALSE, FALSE);
    // Start all cases as an anon user.
    $this->drupalLogout();
  }

  /**
   * Test a gallery embedded in a view row that is dependent on the Juicebox
   * cache.
   */
  public function testSubRequestDependent() {
    $node = $this->node;
    $xml_path = 'juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/_custom';
    $xml_url = \Drupal::url('juicebox.xml_field', array('entityType' => 'node', 'entityId' => $node->id(), 'fieldName' => $this->instFieldName, 'displayName' => '_custom'));
    // Get the urls to the test image and thumb derivative used by default.
    $uri = \Drupal\file\Entity\File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    $test_thumb_url = entity_load('image_style', 'juicebox_square_thumb')->buildUrl($uri);
    // Check for correct embed markup. This will also prime the cache.
    $content = $this->drupalGet('juicebox_test_row_formatter');
    $this->assertRaw(trim(json_encode(array('configUrl' => $xml_url)), '{}"'), 'Gallery setting found in Drupal.settings.');
    $this->assertRaw('id="node--' . $node->id() . '--' . str_replace('_', '-', $this->instFieldName) . '---custom"', 'Embed code wrapper found.');
    $this->assertRaw(Html::escape($test_image_url), 'Test image found in embed code');
    // Extract the xml-source values from the XML.
    $matches = array();
    // In the pattern below we have to use four (yeah, FOUR) backslashes to
    // match a SINGLE literal backslash. Our source will contain an encoded
    // (JSON) "&" character as "\u0026", but we don't want the regex to confuse
    // that with an actaul "&" char in the pattern itself.
    preg_match('|xml-source-path=([a-z1-9_-]+)\\\\u0026xml-source-id=([a-z1-9-]+)|', $content, $matches);
    $this->assertNotNull($matches[1], 'xml-source-path value found in Drupal.settings.');
    $this->assertNotNull($matches[2], 'xml-source-id value found in Drupal.settings.');
    // Check for correct XML. This example is dependent on a sub-request XML
    // lookup, so everything below would fail without that feature.
    $this->drupalGet($xml_path, array('query' => array('xml-source-path' => $matches[1], 'xml-source-id' => $matches[2])));
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'Valid XML detected.');
    $this->assertRaw('imageURL="' . Html::escape($test_image_url), 'Test image found in XML.' . $test_image_url);
    $this->assertRaw('thumbURL="' . Html::escape($test_thumb_url), 'Test thumbnail found in XML.' . $test_thumb_url);
    $this->assertRaw('backgroundcolor="green"', 'Custom background setting from pseudo field instance config found in XML.');
  }

}
