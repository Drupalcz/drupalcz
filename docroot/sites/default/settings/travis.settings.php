<?php

// Set content directory for default_content_deploy.
$config['content_directory'] = '../content';

// Run Travis tests against prod config.
$config['config_split.config_split.prod']['status'] = TRUE;
$config['config_split.config_split.default_content']['status'] = TRUE;
