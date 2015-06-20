<?php

class DrupalCZCommentsBlogs extends DrupalCZComments {
  public $source = 'blog';
  public $target = 'blog';
  public $pidDB = 'migrate_map_drupalczcommentsblogs';
  public $nidDB = 'migrate_map_drupalcznodesblogs';
}
