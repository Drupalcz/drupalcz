<?php

class DrupalCZCommentsArticles extends DrupalCZComments {
  public $source = 'story';
  public $target = 'blog';
  public $pidDB = 'migrate_map_drupalczcommentsarticles';
  public $nidDB = 'migrate_map_drupalcznodesarticles';
}