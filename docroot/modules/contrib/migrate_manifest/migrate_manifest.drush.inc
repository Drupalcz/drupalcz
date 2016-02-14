<?php

/**
 * @file
 * Allow manifests to be run from the command line.
 */

use Drupal\migrate_manifest\MigrateManifest;

/**
 * Implements hook_drush_command().
 */
function migrate_manifest_drush_command() {
  $items['migrate-manifest'] = [
    'description' => 'Execute the migrations as specified in a manifest file.',
    'arguments' => [
      'manifest' => 'The path to the manifest file',
    ],
    'required-arguments' => 1,
    'options' => [
      'legacy-db-url' => [
        'description' => 'A Drupal 6 style database URL.',
        'example-value' => 'mysql://root:pass@127.0.0.1/db',
        'required' => TRUE,
      ],
    ],
    'drupal dependencies' => ['migrate_manifest'],
    // Maintained for compatibility with inital release
    'aliases' => ['migrate-manifest2'],
  ];
  $items['migrate-template-list'] = [
    'description' => 'Lists migration templates available to run.',
    'options' => [
      'tag' => [
        'description' => 'Template tag',
        'example-value' => 'Drupal 6',
        'required' => FALSE,
      ],
    ],
    'drupal dependencies' => ['migrate_manifest'],
  ];

  return $items;
}

/**
 * Import from a manifest file.
 *
 * @param string $manifest
 *   The path to the manifest file.
 * @return array
 */
function drush_migrate_manifest($manifest) {
  try {
    $manifest_runner = new MigrateManifest($manifest);
    return $manifest_runner->import();
  }
  catch (\Exception $e) {
    drush_set_error('MIGRATE_ERROR', $e->getMessage());
  }

  drush_invoke_process('@self', 'cache-rebuild', [], [], FALSE);
}

/**
 * Import from a manifest file.
 *
 * @param string $manifest
 *   The path to the manifest file.
 * @return array
 */
function drush_migrate_manifest_migrate_template_list() {
  /** @var \Drupal\Migrate\MigrateTemplateStorage $template_storage */
  $template_storage = \Drupal::service('migrate.template_storage');

  $tag = drush_get_option('tag');
  if ($tag) {
    $templates = $template_storage->findTemplatesByTag($tag);
  }
  else {
    $templates = $template_storage->getAllTemplates();
  }


  foreach ($templates as $template) {
    drush_print($template['id']);
  }
}
