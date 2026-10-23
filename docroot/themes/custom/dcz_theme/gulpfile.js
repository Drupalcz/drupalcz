// Include gulp.
var gulp = require('gulp');
var browserSync = require('browser-sync').create();
var config = require('./config.json');
var configLocal = require('./config_local.json');

// Include plugins.
var sass = require('gulp-sass')(require('sass'));
var imagemin = require('gulp-imagemin');
var pngcrush = require('imagemin-pngcrush');
var plumber = require('gulp-plumber');
var notify = require('gulp-notify');
var autoprefix = require('gulp-autoprefixer');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');
var sassLint = require('gulp-sass-lint');
var jshint = require('gulp-jshint');
var del = require('del');

// CSS. Expanded style.css is for reading; style.min.css is what the theme loads.
function compileCss(compressed) {
  var stream = gulp.src(config.css.src, { encoding: false })
    .pipe(plumber({
      errorHandler: function (error) {
        notify.onError({
          title:    "Gulp",
          subtitle: "Failure!",
          message:  "Error: <%= error.message %>",
          sound:    "Beep"
        }) (error);
        this.emit('end');
      }}));

  if (!compressed) {
    stream = stream.pipe(sourcemaps.init());
  }

  stream = stream
    .pipe(sass({
      style: compressed ? 'compressed' : 'expanded'
    }).on('error', sass.logError))
    .pipe(autoprefix({ overrideBrowserslist: ['last 2 versions', '> 1%', 'ie 9', 'ie 10'] }));

  if (compressed) {
    stream = stream.pipe(rename({ suffix: '.min' }));
  }
  else {
    stream = stream.pipe(sourcemaps.write('./'));
  }

  return stream
    .pipe(gulp.dest(config.css.dest))
    .pipe(browserSync.reload({ stream: true, match: '**/*.css' }));
}

gulp.task('css', function() {
  return compileCss(false);
});

gulp.task('css-min', function() {
  return compileCss(true);
});

// Compress images.
gulp.task('images', function () {
  return gulp.src(config.images.src, { encoding: false })
    .pipe(imagemin({
      progressive: true,
      svgoPlugins: [{ removeViewBox: false }],
      use: [pngcrush()]
    }))
    .pipe(gulp.dest(config.images.dest));
});

// Concat all js files into one file.
gulp.task('delete_temp_file', function() {
  return del([
    './assets/javascript/tmp/index.js'
  ]);
});
// Concat all js files into one file.
gulp.task('scripts', gulp.series('delete_temp_file', function() {
  return gulp.src(config.js.src, { encoding: false })
    .pipe(plumber({
      errorHandler: function (error) {
        notify.onError({
          title: 'Gulp scripts processing',
          subtitle: 'Failure!',
          message: 'Error: <%= error.message %>',
          sound: 'Beep'
        })(error);
        this.emit('end');
      }}))
    .pipe(sourcemaps.init())
    .pipe(concat('./index.js'))
    .pipe(gulp.dest('./assets/javascript/tmp'))
    .pipe(rename(config.js.file))
    .pipe(gulp.dest(config.js.dest))
    .pipe(sourcemaps.write('./maps'))
    .pipe(notify({message: 'Rebuild all custom scripts. Please refresh your browser'}));
}));

// Watch task.
gulp.task('watch', function() {
  gulp.watch(config.css.src, gulp.series(gulp.parallel('css', 'css-min'), 'sass-lint'));
  gulp.watch(config.images.src, gulp.series('images'));
  gulp.watch(config.js.src, gulp.series('scripts', 'js-lint'));
});

// Static Server + Watch
gulp.task('serve', function(done) {
  // ddev/ddev-browsersync publishes container port 3000 at https://<project>.ddev.site:3000.
  var hostname = process.env.DDEV_HOSTNAME || configLocal.browserSyncHost;
  var proxy = configLocal.browserSyncProxy;
  if (process.env.DDEV_PRIMARY_URL) {
    proxy = process.env.DDEV_PRIMARY_URL.replace(/^https:/, 'http:');
  }
  browserSync.init({
    open: false,
    ui: false,
    host: hostname,
    proxy: proxy,
    port: 3000
  });
  done();
});

// SCSS Linting.
gulp.task('sass-lint', function() {
  return gulp.src(config.css.src, { encoding: false })
      .pipe(sassLint())
      .pipe(sassLint.format())
      .pipe(sassLint.failOnError())
});

// JS Linting.
gulp.task('js-lint', function() {
  return gulp.src(config.js.src, { encoding: false })
    .pipe(jshint())
    .pipe(jshint.reporter('default'));
});

// Default Task
gulp.task('default', gulp.series(gulp.parallel('css', 'css-min', 'scripts', 'js-lint', 'sass-lint'), gulp.parallel('watch', 'serve')));
