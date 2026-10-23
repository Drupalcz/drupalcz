<?php

/**
 * @file
 * Post update functions for Slack Invite.
 */

/**
 * Record HAL's removed hal_post_update_delete_settings() as run.
 *
 * The site never ran it before HAL moved to contrib, and contrib HAL 2.x lists
 * it in hook_removed_post_updates(), so every update run reports HAL as too old
 * to update. Do what it did, dropping the obsolete
 * bc_file_uri_as_url_normalizer key from hal.settings, and record it. This
 * lives here because HAL is a dependency, not a module of ours, and
 * slack_invite stays enabled on every environment.
 */
function slack_invite_post_update_record_hal_delete_settings() {
  $key_value = \Drupal::keyValue('post_update');
  $existing_updates = $key_value->get('existing_updates', []);
  if (in_array('hal_post_update_delete_settings', $existing_updates, TRUE)) {
    return t('HAL post update is already recorded.');
  }
  \Drupal::configFactory()->getEditable('hal.settings')
    ->clear('bc_file_uri_as_url_normalizer')
    ->save();
  $existing_updates[] = 'hal_post_update_delete_settings';
  $key_value->set('existing_updates', $existing_updates);
  return t('Recorded hal_post_update_delete_settings as run.');
}
