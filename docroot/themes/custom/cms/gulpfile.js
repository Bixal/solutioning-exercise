/**
 * @file
 */

'use strict';

// Include gulp.
var gulp = require('gulp');

// Include Our Plugins.
var postcss         = require('gulp-postcss');
var sass            = require('gulp-sass');
var sassGlob        = require('gulp-sass-glob');
var sourcemaps      = require('gulp-sourcemaps');
var scssLint        = require('gulp-scss-lint');
var scssLintStylish = require('gulp-scss-lint-stylish');

// Compile Our Sass.
gulp.task('sass', function () {
  return gulp
    .src('source/*.scss')
    .pipe(sassGlob())
    .pipe(sourcemaps.init())
    .pipe(
      sass({
        includePaths: ['node_modules'],
        outputStyle: 'compressed'
      })
    )
    .pipe(
      postcss([
        require('autoprefixer')({
          remove: false,
          browsers: ['last 2 versions']
        }),
      ])
    )
    .pipe(sass())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('css'));
});

// SCSS linter.
gulp.task('scssLint', function () {
  return gulp.src(['scss/**/*.scss'])
    .pipe(scssLint({
      customReport: scssLintStylish
    }));
});

// Watch Files For Changes.
gulp.task('watch', function () {
  gulp.watch('source/**/*.scss', ['sass']);
});

// Default Task.
gulp.task('default', ['sass', 'watch']);
