var gulp = require('gulp');

var jshint = require('gulp-jshint');
//var sass = require('gulp-sass');
var less = require('gulp-less-sourcemap');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var cssmin = require('gulp-cssmin');
var plumber = require('gulp-plumber');


var path = require('path');

var dev = './bower_components',
    dev_css = '/_css',
    dest_css = path.join(__dirname, '/public/assets/css'),
    dev_js = '/_js';
dest_js = path.join(__dirname, '/public/assets/js'),

    console.log(path.join(__dirname, dev, dev_css));
console.log(path.join(__dirname, dev, dev_js));


gulp.task('copyfonts', function () {
    return gulp.src(path.join(__dirname, dev, dev_css, '/fonts/files/**/*')).
        pipe(gulp.dest(dest_css + '/fonts/'));
});

gulp.task('copyjslibs', function () {
    gulp.src(path.join(__dirname, dev, 'jquery/jquery.min.js')).pipe(gulp.dest(dest_js));
});


gulp.task('copy_less', function () {
    gulp.src(path.join(__dirname, dev, '/bootstrap/less/**/*'))
        .pipe(gulp.dest(path.join(__dirname, dev, '/_css/bootstrap')));
});

gulp.task('less', function () {
    return gulp.src(path.join(__dirname, dev, dev_css, '/global.less'))
        .pipe(plumber())
        .pipe(less({generateSourceMap: false}))
        .pipe(cssmin({keepSpecialComments: 0}))
        .pipe(rename('app.min.css'))
        .pipe(plumber.stop())
        .pipe(gulp.dest(dest_css));
});


gulp.task('scripts', function () {

    gulp.src([
            path.join(__dirname, dev, '/script.js/dist/script.js'),
            path.join(__dirname, dev, '/modernizr/modernizr.js'),
            path.join(__dirname, dev, dev_js, '/enter.js')
        ])
        .pipe(plumber())
        .pipe(concat('scripts.js'))
        .pipe(rename('scripts.min.js'))
        .pipe(uglify())
        .pipe(plumber.stop())
        .pipe(gulp.dest(dest_js));

    gulp.src([
        'bower_components/bootstrap/js/transition.js',
//            'bower_components/bootstrap/js/alert.js',
        'bower_components/bootstrap/js/button.js',
//            'bower_components/bootstrap/js/carousel.js',
        'bower_components/bootstrap/js/collapse.js',
        'bower_components/bootstrap/js/dropdown.js',
//            'bower_components/bootstrap/js/modal.js',
        'bower_components/bootstrap/js/tooltip.js',
        'bower_components/bootstrap/js/popover.js',
        'bower_components/bootstrap/js/scrollspy.js',
//            'bower_components/bootstrap/js/tab.js',
        'bower_components/bootstrap/js/affix.js'
    ], {base: path.join(__dirname, dev, '/bootstrap/js/')})
        .pipe(concat('bootstrap.js'))
        .pipe(rename('bootstrap.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(dest_js));

    gulp.src([
            'bower_components/Snap.svg/dist/snap.svg.js',
            'bower_components/underscore/underscore.js',
            'bower_components/fastclick/lib/fastclick.js',
        ])
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest(dest_js))
        .pipe(uglify())
        .pipe(gulp.dest(dest_js));

    gulp.src([
        'bower_components/swiper/dist/idangerous.swiper.js',
        'bower_components/swiper-smooth-progress/dist/idangerous.swiper.progress.js'
    ], {base: path.join(__dirname, dev, '/')})
        .pipe(concat('swiper.js'))
        .pipe(rename('swiper.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(dest_js));

});

console.log(path.join(__dirname, dev, '/bootstrap/js'));

gulp.task('watch', function () {
    gulp.watch(dev + dev_css + '/**/*', ['copyfonts', 'less']);
    gulp.watch(dev + dev_js + '/**/*', ['scripts']);
    gulp.watch(path.join(__dirname, 'gulpfile.js'), ['default']);
});

gulp.task('default', [
    'copyjslibs',
    'scripts',
//    'copy_less',
    'copyfonts',
    'less'
]);
