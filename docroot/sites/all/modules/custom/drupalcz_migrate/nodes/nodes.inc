<?php

abstract class DrupalCZNodes extends Migration {
  public $source;
  public $target;
  public $migratedFiles = array();
  
  public function __construct() {   
    parent::__construct(MigrateGroup::getInstance('examplegroup'));

    $this->description = t('Migrate categories');
    $query = db_select(D6_DB . '.node', 'n');
    $query->join(D6_DB . '.node_revisions', 'r', 'r.nid = n.nid'); // body field is in revisions table
    $query->fields('n')
      ->fields('r')
      ->condition('n.type', $this->source);
    $this->source = new MigrateSourceSQL($query);
    $this->destination = new MigrateDestinationNode($this->target);
    $this->map = new MigrateSQLMap($this->machineName,
      array(
        'nid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'D6 Unique Node ID',
          'alias' => 'n',
        )
      ),
      MigrateDestinationTerm::getKeySchema()
    );

    // Create the field mappings
    $this->addFieldMapping('title', 'title');
    $this->addFieldMapping('uid', 'uid');
    $this->addFieldMapping('field_body', 'body');
    $this->addFieldMapping('status', 'status');
    $this->addFieldMapping('created', 'created');
    $this->addFieldMapping('changed', 'changed');
    $this->addFieldMapping('promote', 'promote');
  }
  
  public function setTags($entity, $row, $field, $vid, $db) {
    // Get all terms associated with this node
    $query = db_select(D6_DB . '.term_node', 't');
    $query->join(D6_DB . '.term_data', 'd', 'd.tid = t.tid');
    $query->fields('t')
      ->condition('t.nid', $row->nid)
      ->condition('d.vid', $vid);
    $result = $query->execute();
    
    $tids = array();
    while ($row2 = $result->fetch()) {
      $tids[] = $row2->tid;
    }

    // Add them to the new node
    $entity->{$field} = array(LANGUAGE_NONE => array());
    foreach ($tids as $tid) {
      $entity->{$field}[LANGUAGE_NONE][] = array('tid' => $this->newTidGet($tid, $db)); 
    } 
  }
  
  public function newTidGet($old, $db) {
    $result = db_query('SELECT destid1 FROM {' . $db . '} WHERE sourceid1 = :old', array(':old' => $old));
    $new = $result->fetch()->destid1;    
    
    return $new;
  }
  
  public function migrateAttachements($entity, $row, $field, $path) {
    $this->migratedFiles[$row->nid] = array();
    
    // Get all attachements
    $query = db_select(D6_DB . '.upload', 'u');
    $query->join(D6_DB . '.files', 'f', 'f.fid = u.fid');
    $query->fields('u')
      ->fields('f')
      ->condition('u.nid', $row->nid);
    $result = $query->execute();

    $files = array();
    while ($row2 = $result->fetch()) {
      $files[] = $row2;
    }
    
    if ($files) {
      file_prepare_directory($path, FILE_CREATE_DIRECTORY);
      $entity->{$field} = array(LANGUAGE_NONE => array());
      foreach ($files as $old_file) {

        // Upload file
        $filepath = $old_file->filepath;
        $filename = drupal_basename($filepath);
        $handle = fopen($filepath, "r");
        $contents = fread($handle, filesize($filepath));
        fclose($handle);        
        $file = file_save_data($contents, "$path/$filename", FILE_EXISTS_REPLACE);
        $file->display = 1;
     
        // Add file to new node
        $entity->{$field}[LANGUAGE_NONE][] = (array) $file;
        
        $this->migratedFiles[$row->nid][] = $old_file;
      }
    }
  }
  
  public function updatePathsInBody($entity, $row, $field) {
    // Replace linked attachments in the body by the new paths.
    $files = $this->migratedFiles[$row->nid];
    if ($files) {
      foreach ($files as $id => $old) {
        $uri = $entity->{$field}[LANGUAGE_NONE][$id]['uri'];
        $path = str_replace('public:/', '/sites/default/files', $uri);

        $body = $entity->field_body[LANGUAGE_NONE][0]['value'];
        $body = str_replace('http://drupal.cz/' . $old->filepath ,$path, $body);
        $body = str_replace('http://www.drupal.cz/' . $old->filepath ,$path, $body);
        $body = str_replace('/' . $old->filepath, $path, $body);
        
        $entity->field_body[LANGUAGE_NONE][0]['value'] = $body;
      }
      
      node_save($entity);
    }  
  }
}
