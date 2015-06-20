<?php

abstract class DrupalCZComments extends Migration {
  public $cource;
  public $target;
  public $pidDB;
  public $nidDB;

  public function __construct() {
    parent::__construct(MigrateGroup::getInstance('examplegroup'));

    $this->description = t('Migrate comments');
    $query = db_select(D6_DB . '.comments', 'c');
    $query->join(D6_DB . '.node', 'n', 'n.nid = c.nid');
    $query->fields('c')
      ->condition('n.type', $this->source)
      ->orderBy('c.pid', 'ASC');

    $this->source = new MigrateSourceSQL($query);
    $this->destination = new MigrateDestinationComment('comment_node_' . $this->target);
    $this->map = new MigrateSQLMap($this->machineName,
      array(
        'cid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'D6 Unique Comment ID',
          'alias' => 'c',
        )
      ),
      MigrateDestinationTerm::getKeySchema()
    );

    // Create the field mappings
    $this->addFieldMapping('nid', 'nid');
    $this->addFieldMapping('uid', 'uid');
    $this->addFieldMapping('subject', 'subject');
    $this->addFieldMapping('hostname', 'hostname');
    $this->addFieldMapping('comment_body', 'comment');
    $this->addFieldMapping('created', 'created');
    $this->addFieldMapping('status')->defaultValue(TRUE);
    $this->addFieldMapping('thread', 'thread');
    $this->addFieldMapping('name', 'name');
    $this->addFieldMapping('mail', 'mail');
    $this->addFieldMapping('homepage', 'homepage');
  }

  public function prepare($entity, stdClass $row) {
    $entity->nid = $this->newNidGet($row->nid);
    $entity->pid = $this->newPidGet($row->pid);
  }

  public function newNidGet($old) {
    $result = db_query('SELECT destid1 FROM {' . $this->nidDB . '} WHERE sourceid1 = :old', array(':old' => $old));
    $new = $result->fetch()->destid1;

    return $new;
  }

  public function newPidGet($old) {
    if ($old) {
      $result = $parent = db_query('SELECT destid1 FROM {' . $this->pidDB . '} WHERE sourceid1 = :pid', array(':pid' => $old));
      $new = $result->fetch()->destid1;
    }
    else {
      $new = 0;
    }

    return $new;
  }
}
