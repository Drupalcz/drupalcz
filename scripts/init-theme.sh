#! /bin/sh

print_rebuild="n"
if [ ! -f .lando.local.yml ]; then
  cp sass.lando.local.yml .lando.local.yml
  print_rebuild="y"
fi

if [ ! -f docroot/themes/custom/dcz_theme/config_local.json ]; then
  cp docroot/themes/custom/dcz_theme/default.config_local.json docroot/themes/custom/dcz_theme/config_local.json
fi

if [ "$print_rebuild" = "y" ]; then
  echo '''
  Now please rebuild lando using `lando rebuild`. Use `lando gulp css` to compile CSS, `lando gulp` for watching
  changes.
  '''
fi



