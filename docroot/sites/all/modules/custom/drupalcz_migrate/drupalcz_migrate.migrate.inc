<?php

function drupalcz_migrate_migrate_api() {
  $api = array(
    'api' => 2,
    'migrations' => array(
      'DrupalCZTermsCategories' => array('class_name' => 'DrupalCZTermsCategories'),
      'DrupalCZTermsForums'      => array('class_name' => 'DrupalCZTermsForums'),

      'DrupalCZCommentsArticles' => array('class_name' => 'DrupalCZCommentsArticles'),
      'DrupalCZCommentsBlogs'    => array('class_name' => 'DrupalCZCommentsBlogs'),
      'DrupalCZCommentsForums'   => array('class_name' => 'DrupalCZCommentsForums'),

      'DrupalCZNodesArticles' => array('class_name' => 'DrupalCZNodesArticles'),
      'DrupalCZNodesBlogs' => array('class_name' => 'DrupalCZNodesBlogs'),
      'DrupalCZNodesForums' => array('class_name' => 'DrupalCZNodesForums'),

      'DrupalCZUsers' => array('class_name' => 'DrupalCZUsers'),
    ),
  );

  return $api;
}
