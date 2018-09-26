<?php

// Run Travis tests against prod config.
$config['config_split.config_split.prod']['status'] = TRUE;

/**
 * Install profile.
 *
 * It needs to be here. Otherwise install process edits this file.
 */
$settings['install_profile'] = 'minimal';
/**
 * For custom installation
 */
$config_directories['sync'] = "../config/default";
