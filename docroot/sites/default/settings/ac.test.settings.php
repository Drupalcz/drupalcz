<?php

// Turn off mail.
$conf['mail_system'] = array(
  'default-system' => 'DevelMailLog',
);

// Keep stage as close to prod as possible.
$config['config_split.config_split.prod']['status'] = TRUE;
