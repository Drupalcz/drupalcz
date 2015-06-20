<?php

class DrupalCZCommentsForums extends DrupalCZComments {
  public $source = 'forum';
  public $target = 'forum';
  public $pidDB = 'migrate_map_drupalczcommentsforums';
  public $nidDB = 'migrate_map_drupalcznodesforums';
}
