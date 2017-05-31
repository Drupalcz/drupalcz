<?php

// Lock dev.
secure_the_site_please();

// Turn off mail.
$conf['mail_system'] = array(
  'default-system' => 'DevelMailLog',
);
