#! /bin/sh

# enyo/dropzone ships the built library in dist/. dropzonejs looks for
# dropzone-min.js and dropzone.css at the library root
# (dropzonejs_library_info_alter()). docroot/libraries is gitignored, so copy
# those two files into place from Composer's post-install and post-update scripts.

SRC="vendor/enyo/dropzone/dist"
DEST="docroot/libraries/dropzone"

if [ ! -d "$SRC" ]; then
  echo "Dropzone library not installed. Skipping."
  exit 0
fi

if [ ! -f "$SRC/dropzone-min.js" ] || [ ! -f "$SRC/dropzone.css" ]; then
  echo "Dropzone dist is missing dropzone-min.js or dropzone.css." >&2
  exit 1
fi

rm -rf "$DEST"
mkdir -p "$DEST"
cp "$SRC/dropzone-min.js" "$DEST/dropzone-min.js"
cp "$SRC/dropzone.css" "$DEST/dropzone.css"
echo "Dropzone library installed."
