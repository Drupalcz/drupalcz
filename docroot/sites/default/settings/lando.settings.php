<?php

/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/SITE/settings.loc.php'.
 */

$loc_db_name = 'drupal9';
$loc_db_user = 'drupal9';
$loc_db_pass = 'drupal9';
$loc_db_host = 'database';
$loc_db_port = '3306';

$databases = [
  'default' =>
    [
      'default' =>
        [
          'database' => $loc_db_name,
          'username' => $loc_db_user,
          'password' => $loc_db_pass,
          'host' => $loc_db_host,
          'port' => $loc_db_port,
          'driver' => 'mysql',
          'prefix' => '',
        ],
    ],
];

/**
 * Setup files on local.
 */
$settings['file_public_path'] = "sites/default/files";
$settings['file_private_path'] = "/app/web/sites/default/files/private";
$settings["file_temp_path"] = '/tmp';

/**
 * Skip file system permissions hardening on local.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Access control for update.php script.
 */
$settings['update_free_access'] = TRUE;

/**
 * Enable local development services.
 */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

/**
 * Disable Drupal caching.
 */
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
$settings['rebuild_access'] = TRUE;

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 */
$settings['hash_salt'] = 'LOCAL_ONLY';

/**
 * Simulate config we have available on Acquia.
 *
 * Get your own keys:
 * * Slack token: https://api.slack.com/custom-integrations/legacy-tokens .
 */
$config['slack_invite.settings']['token'] = "DUMMY_TOKEN";

/**
 * Set active config split.
 */
$config['config_split.config_split.default_content']['status'] = TRUE;
$config['config_split.config_split.dev']['status'] = TRUE;
$config['config_split.config_split.test']['status'] = FALSE;
$config['config_split.config_split.cleantalk']['status'] = FALSE;
$config['config_split.config_split.prod']['status'] = FALSE;
