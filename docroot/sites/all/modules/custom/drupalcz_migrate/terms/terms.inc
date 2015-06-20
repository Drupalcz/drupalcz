<?php

abstract class DrupalCZTerms extends Migration {
  public function __construct() {
    parent::__construct(MigrateGroup::getInstance('examplegroup'));

    $this->description = t('Migrate categories');
    $query = db_select(D6_DB . '.term_data', 't');
    $query->join(D6_DB . '.term_hierarchy', 'h', 'h.tid = t.tid');
    $query->fields('t')
        ->fields('h')
        ->condition('vid', $this->vid)
        ->orderBy('h.parent', 'ASC');
    $this->source = new MigrateSourceSQL($query);
    $this->destination = new MigrateDestinationTerm($this->voc);

    $this->map = new MigrateSQLMap($this->machineName,
      array(
        'tid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'D6 Unique Term ID',
          'alias' => 't',
        )
      ),
      MigrateDestinationTerm::getKeySchema()
    );

    // Create the field mappings
    $this->addFieldMapping('name', 'name');
    $this->addFieldMapping('description', 'description');
  }

  public function prepare($entity, stdClass $row) {
    $entity->weight = $row->weight;
  }
}
