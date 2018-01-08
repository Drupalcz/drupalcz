// Include gulp.
var gulp = require('gulp');
var browserSync = require('browser-sync').create();
var config = require('./config.json');
var configLocal = require('./config_local.json');

// Include plugins.
var sass = require('gulp-sass');
var imagemin = require('gulp-imagemin');
var pngcrush = require('imagemin-pngcrush');
var plumber = require('gulp-plumber');
var notify = require('gulp-notify');
var autoprefix = require('gulp-autoprefixer');
var glob = require('gulp-sass-glob');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');
var sassLint = require('gulp-sass-lint');
var jshint = require('gulp-jshint');
var del = require('del');

// CSS.
gulp.task('css', function() {
  return gulp.src(config.css.src)
    .pipe(glob())
    .pipe(plumber({
      errorHandler: function (error) {
        notify.onError({
          title:    "Gulp",
          subtitle: "Failure!",
          message:  "Error: <%= error.message %>",
          sound:    "Beep"
        }) (error);
        this.emit('end');
      }}))
    .pipe(sourcemaps.init())
    .pipe(sass({
      style: 'compressed',
      errLogToConsole: true
    }))
    .pipe(autoprefix('last 2 versions', '> 1%', 'ie 9', 'ie 10'))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(config.css.dest))
    .pipe(browserSync.reload({ stream: true, match: '**/*.css' }));
});

// Compress images.
gulp.task('images', function () {
  return gulp.src(config.images.src)
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
gulp.task('scripts', ['delete_temp_file'], function() {
  return gulp.src(config.js.src)
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
});

// Watch task.
gulp.task('watch', function() {
  gulp.watch(config.css.src, ['css', 'sass-lint']);
  gulp.watch(config.images.src, ['images']);
  gulp.watch(config.js.src, ['scripts', 'js-lint']);
});

// Static Server + Watch
gulp.task('serve', ['css', 'scripts', 'js-lint', 'sass-lint', 'watch'], function() {
  browserSync.init({
    open: false,
    host: configLocal.browserSyncHost,
    proxy: configLocal.browserSyncProxy
  });
});

// SCSS Linting.
gulp.task('sass-lint', function() {
  return gulp.src(config.css.src)
      .pipe(sassLint())
      .pipe(sassLint.format())
      .pipe(sassLint.failOnError())
});

// JS Linting.
gulp.task('js-lint', function() {
  return gulp.src(config.js.src)
    .pipe(jshint())
    .pipe(jshint.reporter('default'));
});

// Default Task
gulp.task('default', ['serve']);
