<?php

// Lock test.
secure_the_site_please();

// Turn off mail.
$conf['mail_system'] = array(
  'default-system' => 'DevelMailLog',
);

$config['config_split.config_split.stage']['status'] = TRUE;
