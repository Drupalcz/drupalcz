<?php

/**
 * @file
 * Post update functions for Drupal.cz.
 */

/**
 * Install the Riddle entity type introduced in riddler 2.x.
 *
 * Riddler's own update hooks create riddle entities but never register the
 * entity type, leaving a "Mismatched entity and/or field definitions" warning.
 */
function dcz_rest_post_update_install_riddle_entity_type() {
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  if ($update_manager->getEntityType('riddle')) {
    return t('Riddle entity type is already installed.');
  }
  $entity_type = \Drupal::entityTypeManager()->getDefinition('riddle');
  $update_manager->installEntityType($entity_type);
  return t('Installed the Riddle entity type.');
}
