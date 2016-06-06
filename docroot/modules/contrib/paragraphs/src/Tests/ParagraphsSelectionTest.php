<?php

namespace Drupal\paragraphs\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests paragraphs selection.
 *
 * @group paragraphs
 */
class ParagraphsSelectionTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create paragraphs and article content types.
    $this->drupalCreateContentType(array(
      'type' => 'paragraphed_test',
      'name' => 'paragraphed_test'
    ));
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the revision of paragraphs.
   */
  public function testParagraphsRevisions() {
    $admin_user = $this->drupalCreateUser(array(
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer paragraphs types',
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer node form display',
    ));
    $this->drupalLogin($admin_user);
    static::fieldUIDeleteField('admin/structure/types/manage/paragraphed_test', 'node.paragraphed_test.body', 'Body', 'paragraphed_test');

    // Add two paragraph types.
    $this->addParagraphsType('btext');
    $this->addParagraphsType('dtext');

    // Create a Paragraphs field.
    static::fieldUIAddNewField('admin/structure/types/manage/paragraphed_test', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);

    $this->clickLink(t('Manage form display'));
    $this->drupalPostForm(NULL, array('fields[field_paragraphs][type]' => 'entity_reference_paragraphs'), t('Save'));

    $this->assertAddButtons(['Add btext', 'Add dtext']);

    $this->addParagraphsType('atext');
    $this->assertAddButtons(['Add btext', 'Add dtext', 'Add atext']);

    $this->setParagraphsTypeWeight('dtext', 2, 'node.paragraphed_test.field_paragraphs');
    $this->assertAddButtons(['Add dtext', 'Add btext', 'Add atext']);

    $this->setAllowedParagraphsTypes(['dtext', 'atext'], TRUE);
    $this->assertAddButtons(['Add dtext', 'Add atext']);

    $this->setParagraphsTypeWeight('atext', 1, 'node.paragraphed_test.field_paragraphs');
    $this->assertAddButtons(['Add atext', 'Add dtext']);

    $this->setAllowedParagraphsTypes(['atext', 'dtext', 'btext'], TRUE);
    $this->assertAddButtons(['Add atext', 'Add dtext', 'Add btext']);
  }

  /**
   * Set allowed Paragraphs types.
   *
   * @param array $paragraphs_types
   *   Array of paragraphs types that will be modified.
   * @param boolean $selected
   *   Whether or not the paragraphs types will be enabled.
   */
  protected function setAllowedParagraphsTypes($paragraphs_types, $selected) {
    $edit = [];
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/fields');
    $this->clickLink(t('Edit'));
    foreach ($paragraphs_types as $paragraphs_type) {
      $edit['settings[handler_settings][target_bundles_drag_drop][' . $paragraphs_type . '][enabled]'] = $selected;
    }
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
  }

  /**
   * Set weight of a given Paragraphs type.
   *
   * @param string $paragraphs_type
   *   ID of Paragraph type that will be modified.
   * @param integer $position
   *   Position to be set.
   * @param string $field
   *   Paragraphs field that does the reference.
   */
  protected function setParagraphsTypeWeight($paragraphs_type, $position, $field) {
    $edit = [];
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/fields/' . $field);
    $edit['settings[handler_settings][target_bundles_drag_drop][' . $paragraphs_type . '][weight]'] = $position;
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
  }

  /**
   * Adds a Paragraphs type.
   *
   * @param string $paragraphs_type
   *   Paragraph type name used to create.
   */
  protected function addParagraphsType($paragraphs_type) {
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $edit = ['label' => $paragraphs_type, 'id' => $paragraphs_type];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
  }

  /**
   * Asserts order and quantity of add buttons.
   *
   * @param array $options
   *   Array of expected add buttons in its correct order.
   */
  protected function assertAddButtons($options) {
    $this->drupalGet('node/add/paragraphed_test');
    $buttons = $this->xpath('//input[@class="field-add-more-submit button js-form-submit form-submit"]');
    foreach ($buttons as $key => $button) {
      $this->assertEqual($button['value'], $options[$key]);
    }
    $this->assertTrue(count($buttons) == count($options));
  }

}
