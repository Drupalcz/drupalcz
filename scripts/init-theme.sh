#! /bin/sh

# Prepare the custom theme for front-end builds inside DDEV.
# Composer runs this from the project root: ddev composer run init-theme

THEME="docroot/themes/custom/dcz_theme"

if [ ! -f "$THEME/config_local.json" ]; then
  cp "$THEME/default.config_local.json" "$THEME/config_local.json"
  echo "Created $THEME/config_local.json"
fi

cd "$THEME" || exit 1
npm install || exit 1

echo "Theme dependencies are installed. Compile CSS with \`ddev gulp css\` (style.css) and \`ddev gulp css-min\` (style.min.css). Watch with \`ddev gulp\` (BrowserSync at https://drupalcz.ddev.site:3000)."
