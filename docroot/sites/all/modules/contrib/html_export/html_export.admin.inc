<?php
/**
 * @file
 * Admin form for global html export settings.
 */

/**
 * Implementation of hook_settings().
 */
function _html_export_settings() {
  // allow for global setting for views if its enabled
  if (module_exists('views')) {
    $views = views_get_all_views();
    foreach ($views as $key => $view) {
      // pull out enabled views that have node as the base
      if ($view->base_table == 'node' && !$view->disabled) {
        $options[$key] = $key;
      }
    }
    // build the form
    $form['html_export_available_views'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Views based selectors'),
      '#default_value' => variable_get('html_export_available_views', array()),
      '#options' => $options,
      '#description' => t('Select the views that are available to use as selectors for pulling their query results. Only use these if you know what you are doing.'),
    );
  }
  // list of all types
  $options = array();
  $types = node_get_types();
  foreach ($types as $key => $type) {
    $options[$key] = $type->name;
  }
  // build the form
  $form['html_export_available_types'] = array(
    '#type' => 'select',
    '#multiple' => TRUE,
    '#title' => t('Type based selectors'),
    '#default_value' => variable_get('html_export_available_types', array()),
    '#options' => $options,
    '#description' => t('Select the types that are available for publishing as options.'),
  );
  $form['html_export_other_paths'] = array(
    '#type' => 'textarea',
    '#title' => t('Other paths to export'),
    '#description' => t("Provide one path per line of additional paths to export."),
    '#default_value' => variable_get('html_export_other_paths', '<front>'),
  );
  $form['html_export_dom_remove'] = array(
    '#type' => 'textarea',
    '#title' => t('Default dom removal'),
    '#description' => t("Supply a css style selector to target the removal of. This is especially useful for removing things like the admin toolbar, tabs, or other elements you don't want to have exported. One selectors per line."),
    '#default_value' => variable_get('html_export_dom_remove', ''),
  );
  return system_settings_form($form);
}
