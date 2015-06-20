<?php

class DrupalCZTermsForums extends DrupalCZTerms {
  public $voc = 'forums';
  public $vid = 1;
  
  public function prepare($entity, stdClass $row) {
    parent::prepare($entity, $row);
    
    if ($row->parent) {    
      $parent = db_query('select t.parent from {' . D6_DB . '.term_hierarchy} t where tid = :tid', 
        array(':tid' => $row->tid)
      )->fetch()->parent;  

      $parent = db_query('SELECT destid1 FROM {migrate_map_drupalcztermsforums} WHERE sourceid1 = :old',
        array(':old' => $parent)
      )->fetch()->destid1;

      $entity->parent = array($parent => $parent);
    }
  }
}