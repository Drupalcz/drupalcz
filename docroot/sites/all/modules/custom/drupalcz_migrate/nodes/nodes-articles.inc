<?php

class DrupalCZNodesArticles extends DrupalCZNodes {
  public $source = 'story';
  public $target = 'blog';

  public function prepare($entity, stdClass $row) {
    $entity->field_body['und'][0]['format'] = 'filtered_html';
    $entity->field_body['und'][0]['value_format'] = 'filtered_html';

    $this->setTags($entity, $row, 'field_tax_tags', 4, 'migrate_map_drupalcztermscategories');
    $path = 'public://blogs/attachements/' . date('Y', $row->created) . '/' . date('m', $row->created) . '/dcz6/';
    $this->migrateAttachements($entity, $row, 'field_attachment', $path);
  }

  public function complete($entity, stdClass $row) {
    $this->updatePathsInBody($entity, $row, 'field_attachment');
  }
}

