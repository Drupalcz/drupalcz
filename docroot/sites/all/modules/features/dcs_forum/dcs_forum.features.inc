<?php
/**
 * @file
 * dcs_forum.features.inc
 */

/**
 * Implements hook_ctools_plugin_api().
 */
function dcs_forum_ctools_plugin_api($module = NULL, $api = NULL) {
  if ($module == "strongarm" && $api == "strongarm") {
    return array("version" => "1");
  }
}

/**
 * Implements hook_node_info().
 */
function dcs_forum_node_info() {
  $items = array(
    'forum' => array(
      'name' => t('Forum'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  drupal_alter('node_info', $items);
  return $items;
}
