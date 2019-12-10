'use strict';

// Include gulp
var gulp = require('gulp');

// Include Our Plugins
var jshint = require('gulp-jshint');
var sass = require('gulp-sass');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var postcss = require('gulp-postcss');
var cssmin = require('gulp-cssmin');
var plumber = require('gulp-plumber');
var autoprefixer = require('autoprefixer');
var notify = require('gulp-notify');
var scssLint = require('gulp-scss-lint');
var scssLintStylish = require('gulp-scss-lint-stylish');
var sassGlob     = require('gulp-sass-glob');
var sourcemaps   = require('gulp-sourcemaps');

// // Compile Our Sass
gulp.task('sass', function() {
  return gulp.src('src/sass/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({
      includePaths: ['node_modules'],
      outputStyle: 'compressed'
    }))
    .pipe(sassGlob())
    .pipe(plumber({
      errorHandler: notify.onError("Error: <%= error.message %>")
    }))
    .pipe(sass())
    .pipe(postcss([autoprefixer({
      browsers: ['last 2 versions']
    })]))
    .pipe(gulp.dest('public/css'))
    .pipe(cssmin())
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('public/css'));
});

// gulp.task('sass', function () {
//   return gulp.src('./src/sass/*.scss')
//     .pipe(sass({
//       includePaths: ['node_modules']
//     }))
//     .pipe(sourcemaps.init())
//     .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
//     .pipe(sourcemaps.write('./maps'))
//     .pipe(gulp.dest('./css'));
// });

//Concatenate & Minify JS
gulp.task('scripts', function() {
  return gulp.src(['src/js/*.js'])
    .pipe(sourcemaps.init())
    .pipe(plumber({
      errorHandler: notify.onError("Error: <%= error.message %>")
    }))
    .pipe(jshint())
    .pipe(jshint.reporter('jshint-stylish'))
    .pipe(concat('scripts.js'))
    // start uglify
    .pipe(uglify())
    .pipe(rename('scripts.min.js'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('public/js'));
});

//SCSS linter
gulp.task('scssLint', function() {
  return gulp.src(['scss/**/*.scss'])
    .pipe(scssLint({
      // 'config': 'scss_linters.yml',
      customReport: scssLintStylish
    }));
});


// Watch Files For Changes
gulp.task('watch', function() {
  gulp.watch('src/js/**/*.js', ['scripts']);
  gulp.watch('src/sass/**/*.scss', ['sass']);
});

// Default Task
gulp.task('default', ['sass', 'scripts', 'watch']);
