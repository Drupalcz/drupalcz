<?php

namespace Drupal\mollom\Tests;
use Drupal\mollom\Storage\ResponseDataStorage;

/**
 * Verify that Mollom data can be created, read, updated, and deleted.
 * @group mollom
 */
class DataCRUDTest extends MollomTestBase {

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server'];

  protected $useLocal = TRUE;

  /**
   * Verify that Mollom data can be updated.
   *
   * Also verifies that the combined primary/unique database schema index is
   * properly accounted for; i.e., two entities having the same ID but different
   * types must not considered the same.
   */
  function testUpdate() {
    // Create a first data record.
    $data1 = (object) [
      'entity' => 'type1',
      'id' => 123,
      'form_id' => 'type1_form',
      'contentId' => 1,
    ];
    ResponseDataStorage::save($data1);
    $this->assertMollomData($data1->entity, $data1->id, 'contentId', $data1->contentId);

    // Create a second data record; same ID, different entity type.
    $data2 = (object) [
      'entity' => 'type2',
      'id' => 123,
      'form_id' => 'type2_form',
      'contentId' => 2,
    ];
    ResponseDataStorage::save($data2);
    $this->assertMollomData($data2->entity, $data2->id, 'contentId', $data2->contentId);

    // Update the first data record.
    $data1->contentId = 3;
    ResponseDataStorage::save($data1);

    // Verify that both records are correct.
    $this->assertMollomData($data1->entity, $data1->id, 'contentId', $data1->contentId);
    $this->assertMollomData($data2->entity, $data2->id, 'contentId', $data2->contentId);
  }

  /**
   * Verify that Mollom data can be deleted.
   */
  function testDelete() {
    // Create a data record.
    $data1 = (object) array(
      'entity' => 'type1',
      'id' => 123,
      'form_id' => 'type1_form',
      'contentId' => 1,
    );
    ResponseDataStorage::save($data1);

    // Create a second data record; same ID, different entity type.
    $data2 = (object) array(
      'entity' => 'type2',
      'id' => 123,
      'form_id' => 'type2_form',
      'contentId' => 2,
    );
    ResponseDataStorage::save($data2);

    // Verify that both records exist.
    $this->assertMollomData($data1->entity, $data1->id, 'contentId', $data1->contentId);
    $this->assertMollomData($data2->entity, $data2->id, 'contentId', $data2->contentId);

    // Delete the first data record.
    ResponseDataStorage::delete($data1->entity, $data1->id);

    // Verify that only the second record remained and was not changed.
    $this->assertNoMollomData($data1->entity, $data1->id);
    $this->assertMollomData($data2->entity, $data2->id, 'contentId', $data2->contentId);
  }
}
