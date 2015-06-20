<?php

class DrupalCZNodesForums extends DrupalCZNodes {
  public $source = 'forum';
  public $target = 'forum';

  public function prepare($entity, stdClass $row) {
    $entity->field_body['und'][0]['format'] = 'filtered_html';
    $entity->field_body['und'][0]['value_format'] = 'filtered_html';

    $this->setTags($entity, $row, 'taxonomy_forums', 1, 'migrate_map_drupalcztermsforums');
    $path = 'public://forums/attachements/' . date('Y', $row->created) . '/' . date('m', $row->created) . '/dcz6/';
    $this->migrateAttachements($entity, $row, 'field_attachment', $path);
    $this->addDrupalVersion($entity, $row);
  }

  public function complete($entity, stdClass $row) {
    $this->updatePathsInBody($entity, $row, 'field_attachment');
  }

  public function addDrupalVersion($entity, stdClass $row) {
    $mapping = array(
      287 => 6,
      288 => 5,
      289 => 4,
      682 => 3,
    );

    // Get all terms associated with this node
    $query = db_select(D6_DB . '.term_node', 't');
    $query->join(D6_DB . '.term_data', 'd', 'd.tid = t.tid');
    $query->fields('t')
      ->condition('t.nid', $row->nid)
      ->condition('d.vid', 11);
    $result = $query->execute();

    $tids = array();
    while ($row2 = $result->fetch()) {
      if (array_key_exists($row2->tid, $mapping)) {
        $tids[] = $mapping[$row2->tid];
      }
    }

    // Add them to the new node
    $field = 'field_tax_drupal_version';
    $entity->{$field} = array(LANGUAGE_NONE => array());
    foreach ($tids as $tid) {
      $entity->{$field}[LANGUAGE_NONE][] = array('tid' => $tid);
    }
  }
}
