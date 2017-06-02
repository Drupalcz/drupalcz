var gulp = require('gulp');
var livereload = require('gulp-livereload');
var uglify = require('gulp-uglifyjs');
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');
var sourcemaps = require('gulp-sourcemaps');
var imagemin = require('gulp-imagemin');
var pngquant = require('imagemin-pngquant');
var gutil = require('gulp-util');
var concat = require('gulp-concat');
var sizereport = require('gulp-sizereport');
var watch = require('gulp-watch');
var batch = require('gulp-batch');
var jshint = require('gulp-jshint');



var basePaths = {
  src: './',
  dest: './'
};


var paths = {
  scripts: {
    src: basePaths.src + 'javascript/',
    dest: basePaths.dest + 'js/'
  },
  styles: {
    src: basePaths.src + 'sass/',
    dest: basePaths.dest + 'css/'
  }
};

var appFiles = {
  styles: paths.styles.src + '**/*.scss',
  scripts: [paths.scripts.src + '**/*.js']
};

var vendorFiles = {
  styles: '',
  scripts: ''
};

// Allows gulp --dev to be run for a more verbose output
var isProduction = true;
var sassStyle = 'compressed';

if(gutil.env.dev === true) {
  isProduction = false;
  sassStyle = 'expanded';
}


gulp.task('styles', function () {
  gulp.src(appFiles.styles)
    .pipe(sourcemaps.init())
    .pipe(sass({outputStyle: sassStyle}).on('error', sass.logError))
    .pipe(autoprefixer('last 2 version', 'safari 5', 'ie 7', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4'))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(paths.styles.dest));
});


gulp.task('scripts', function () {
  gulp.src(appFiles.scripts)
    .pipe(concat('jssor_view.js'))
    .pipe(isProduction ? uglify() : gutil.noop())
    .pipe(gulp.dest(paths.scripts.dest));
});


gulp.task('sizereport', function () {
  gulp.src([
    paths.styles.dest,
    paths.scripts.dest
  ])
    .pipe(sizereport({
      gzip: true
    }));
});


gulp.task('watch', function(){
  livereload.listen();

  gulp.watch(appFiles.styles, ['styles']);
  gulp.watch(appFiles.scripts, ['uglify']);
  gulp.watch([paths.styles.dest, paths.scripts.dest], function (files){
    livereload.changed(files)
  });
});


gulp.task('lint', function () {
  return gulp.src(appFiles.scripts)
    .pipe(jshint())
    .pipe(jshint.reporter('default'));
});


gulp.task('default', ['styles', 'scripts', 'sizereport']);
