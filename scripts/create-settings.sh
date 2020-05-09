#! /bin/sh

if [ ! -e docroot/sites/default/settings/local.settings.php ]; then
  cp docroot/sites/default/settings/default.local.settings.php docroot/sites/default/settings/local.settings.php
  sed -i "s/@@DB_NAME@@/drupal8/g" docroot/sites/default/settings/local.settings.php
  sed -i "s/@@DB_USER@@/drupal8/g" docroot/sites/default/settings/local.settings.php
  sed -i "s/@@DB_PASS@@/drupal8/g" docroot/sites/default/settings/local.settings.php
  sed -i "s/@@DB_HOST@@/database/g" docroot/sites/default/settings/local.settings.php
  echo Settings file was created.
else
  echo Settings file already exists. Skipping creation.
fi

