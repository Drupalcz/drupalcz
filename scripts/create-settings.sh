#! /bin/sh

if [ ! -e docroot/sites/default/settings/local.settings.php ]; then
  echo "<?php\n\n// This is for your customizations." > docroot/sites/default/settings/local.settings.php
  echo Settings file was created.
else
  echo Settings file already exists. Skipping creation.
fi
