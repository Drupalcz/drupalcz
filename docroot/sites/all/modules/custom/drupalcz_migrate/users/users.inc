<?php

class DrupalCZUsers extends Migration {
  public function __construct() {
    parent::__construct(MigrateGroup::getInstance('examplegroup'));

    $this->description = t('Migrate users');
    $query = db_select(D6_DB . '.users', 'u')->fields('u');
    $this->source = new MigrateSourceSQL($query);
    $this->destination = new MigrateDestinationUser(array('md5_passwords' => TRUE));

    $this->map = new MigrateSQLMap($this->machineName,
      array(
        'uid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'D6 Unique User ID',
          'alias' => 'u',
        )
      ),
      MigrateDestinationUser::getKeySchema()
    );

    // Create the field mappings
    $this->addFieldMapping('is_new')->defaultValue(TRUE);
    $this->addFieldMapping('uid', 'uid')->dedupe('users', 'uid');
    $this->addFieldMapping('name', 'name')->dedupe('users', 'name');
    $this->addFieldMapping('created', 'created');
    $this->addFieldMapping('access', 'access');
    $this->addFieldMapping('login', 'login');
    $this->addFieldMapping('init', 'init');;
    $this->addFieldMapping('status', 'status');
    $this->addFieldMapping('pass', 'pass');
    $this->addFieldMapping('mail', 'mail')->dedupe('users', 'mail');
  }

  public function prepare($entity, stdClass $row) {
    $data = unserialize($row->data);

    if (@$data['info']) {
      $entity->field_user_bio[LANGUAGE_NONE][0] = array('value' => $data['info']);
    }
  }
}
