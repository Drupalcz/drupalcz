#! /bin/sh

# bower-asset/dropzone ships source only — no dist/ — so the Dropzone library has to be
# built before dropzonejs can find it. The deploy pipeline already does this in
# .github/scripts/build.sh; this is the same step for local development.
#
# docroot/libraries is gitignored, so the built files never travel with a checkout and
# every composer install throws them away. Hence the DDEV post-start hook.
#
# dropzonejs_library_info_alter() looks for the files at several paths; dist/min/ is one
# of them, so webpack's own output location is left alone.

DIR="docroot/libraries/dropzone"
JS="$DIR/dist/min/dropzone.min.js"
CSS="$DIR/dist/min/dropzone.min.css"

if [ ! -d "$DIR" ]; then
  echo "Dropzone library not installed. Skipping build."
  exit 0
fi

if [ -f "$JS" ] && [ -f "$CSS" ]; then
  echo Dropzone library already built. Skipping build.
  exit 0
fi

cd "$DIR" || exit 1
yarn install || exit 1

# The library's yarn.lock pins webpack 5.23.0, which hashes with md4. OpenSSL 3 (Node 17+)
# refuses that and the build dies with ERR_OSSL_EVP_UNSUPPORTED; webpack fixed it in 5.54,
# but the lockfile is not ours to bump — composer install replaces the whole directory.
#
# The stylesheets fail regardless: css-loader 5 requires postcss/package.json, which newer
# postcss forbids through its "exports" field. The JS still builds, so let webpack finish
# and compile the SCSS separately with the sass binary it already ships.
NODE_OPTIONS=--openssl-legacy-provider yarn build || true
./node_modules/.bin/sass src/dropzone.scss dist/min/dropzone.min.css \
  --style=compressed --no-source-map --quiet || true

cd - >/dev/null || exit 1

# Judge the build by what it produced, not by webpack's exit code.
if [ ! -f "$JS" ]; then
  echo "Dropzone build failed: $JS is missing." >&2
  exit 1
fi
if [ ! -f "$CSS" ]; then
  echo "Dropzone build failed: $CSS is missing." >&2
  exit 1
fi
echo Dropzone library was built.
