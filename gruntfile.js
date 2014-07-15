module.exports = function (grunt) {
    grunt.initConfig({
        pkg:grunt.file.readJSON('package.json'),
        bootstrap_folder:'bower_components/bootstrap',
        dev_folder:'bower_components',
        dev_css:'/_css',
        dev_js:'/_js',
        assets_folder:'public/assets',
        css_folder:'public/assets/css',
        js_folder:'public/assets/js/app',
//        sass:{
//            dist:{
//                files:{
//                    '<%= js_folder %>/vendor/mmenu/src/css/jquery.mmenu.all.css':'<%= js_folder %>/vendor/mmenu/src/scss/jquery.mmenu.all.scss'
//                }
//            },
//        },
        less:{
            development:{
                options:{
                    compress:true,
                    cleancss:true,
                    optimization:2
                },
                files:{
                    "<%= css_folder %>/app.min.css":[
                        "<%= dev_folder %><%= dev_css %>/global.less",
                    ],
                }
            }
        },
        copy:{
            icons:{expand: true, flatten: true, src:'<%= dev_folder %><%= dev_css %>/icons/files/**/*', dest:'<%= css_folder %>/icons/'},
            fonts:{expand: true, flatten: true, src:'<%= dev_folder %><%= dev_css %>/fonts/files/**/*', dest:'<%= css_folder %>/fonts/'},
            lightbox_js:{src:'<%= dev_folder %>/magnific-popup/dist/jquery.magnific-popup.min.js', dest:'<%= js_folder %>/magnific-popup.js'},
            wow_js:{src:'<%= dev_folder %>/WOW/dist/wow.min.js', dest:'<%= js_folder %>/wow.js'},
            lazy_js:{src:'<%= dev_folder %>/jquery.lazyload/jquery.lazyload.js', dest:'<%= js_folder %>/lazyload.js'},
            lightbox_css:{src:'<%= dev_folder %>/magnific-popup/dist/magnific-popup.css', dest:'<%= js_folder %>/magnific-popup.css'},
            animate_css:{src:'<%= dev_folder %>/animate.css/animate.css', dest:'<%= dev_folder %>/_css/animations/animate.less'},
//            swiper_css:{src:'<%= dev_folder %>/swiper/dist/idangerous.swiper.css', dest:'<%= dev_folder %><%= dev_css %>/slider/slider-swiper.less'},
//            swiper3dflow_css:{src:'<%= dev_folder %>/swiper-3d-flow/dist/idangerous.swiper.3dflow.css', dest:'<%= dev_folder %>/less/sliders/idangerous.swiper.3dflow.less'},
            swiper_smooth_progress_js:{src:'<%= dev_folder %>/swiper-smooth-progress/dist/idangerous.swiper.progress.min.js', dest:'<%= js_folder %>/swiper-progress.js'},
            swiper_hash_js:{src:'<%= dev_folder %>/swiper-hash-navigation/dist/idangerous.swiper.hashnav.min.js', dest:'<%= js_folder %>/swiper-hash.js'},
//            swiper_3dflow_js:{src:'<%= dev_folder %>/swiper-3d-flow/dist/idangerous.swiper.3dflow.min.js', dest:'<%= js_folder %>/vendor/swiper/idangerous.swiper.3dflow.min.js'},
//            swiper_scrollbar_js:{src:'<%= dev_folder %>/swiper-scrollbar/dist/idangerous.swiper.scrollbar.min.js', dest:'<%= js_folder %>/vendor/swiper/idangerous.swiper.scrollbar.min.js'},
//            mmenu_js:{src:'<%= dev_folder %>/jQuery.mmenu/src/js/jquery.mmenu.min.all.js', dest:'<%= js_folder %>/vendor/mmenu/src/js/jquery.mmenu.min.all.js'},
        },

        concat:{
//            app:{
//                src:['<%= dev_folder %>/modernizr/modernizr.full.js', '<%= dev_folder %><%= dev_js %>/app.js'],
//                dest:'<%= dev_folder %><%= dev_js %>/starter-with-modernizr.js'
//            },
            app1:{
                src:['<%= dev_folder %>/modernizr/modernizr.full.js', '<%= dev_folder %><%= dev_js %>/app1.js'],
                dest:'<%= dev_folder %><%= dev_js %>/starter1-with-modernizr.js'
            },

            bootstrap:{
                src:[
                    '<%= bootstrap_folder %>/js/transition.js',
                    '<%= bootstrap_folder %>/js/alert.js',
                    '<%= bootstrap_folder %>/js/button.js',
                    '<%= bootstrap_folder %>/js/carousel.js',
                    '<%= bootstrap_folder %>/js/collapse.js',
                    '<%= bootstrap_folder %>/js/dropdown.js',
                    '<%= bootstrap_folder %>/js/modal.js',
                    '<%= bootstrap_folder %>/js/tooltip.js',
                    '<%= bootstrap_folder %>/js/popover.js',
                    '<%= bootstrap_folder %>/js/scrollspy.js',
                    '<%= bootstrap_folder %>/js/tab.js',
                    '<%= bootstrap_folder %>/js/affix.js',
                ],
                dest:'<%= bootstrap_folder %>/bootstrap.js'
            },
        },


        uglify:{
//            requirejs:{files:{'<%= js_folder %>/require.min.js':['<%= dev_folder %>/requirejs/require.js']}},
//            pace:{files:{'<%= js_folder %>/pace.js':['<%= dev_folder %>/pace/pace.js']}},
//            jaddress:{files:{'<%= js_folder %>/jquery.address.js':['<%= dev_folder %>/jquery-address/src/jquery.address.js']}},
//            skrollr:{files:{'<%= js_folder %>/skrollr.js':['<%= dev_folder %>/skrollr/src/skrollr.js']}},
            jquery:{files:{'<%= js_folder %>/jquery.js':['<%= dev_folder %>/jquery/jquery.js']}},
//            fastclick:{files:{'<%= js_folder %>/fastclick.js':['<%= dev_folder %>/fastclick/lib/fastclick.js']}},
//            bootstrap:{files:{'<%= js_folder %>/bootstrap.js':['<%= concat.bootstrap.dest %>']}},
//            underscore:{files:{'<%= js_folder %>/vendor/underscore/underscore.min.js':['<%= dev_folder %>/underscore/underscore.js']}},
//            app:{files:{'<%= js_folder %>/app.min.js':['<%= dev_folder %><%= dev_js %>/starter-with-modernizr.js']}},
            app1:{files:{'<%= js_folder %>/app1.min.js':['<%= dev_folder %><%= dev_js %>/starter1-with-modernizr.js']}},
//            app:{files:{'<%= js_folder %>/app.min.js':['<%= dev_folder %><%= dev_js %>/app.js']}},
//            backbone:{files:{'<%= js_folder %>/vendor/backbone/backbone.min.js':['<%= dev_folder %>/backbone/backbone.js']}},
//            marionette:{files:{'<%= js_folder %>/vendor/backbone/marionette.min.js':['<%= dev_folder %>/backbone.marionette/lib/backbone.marionette.js']}},
//            snap_svg:{files:{'<%= js_folder %>/vendor/snap.svg/snapsvg.min.js':['<%= dev_folder %>/Snap.svg/dist/snap.svg.js']}},
            swiper:{files:{'<%= js_folder %>/swiper.js':['<%= dev_folder %>/swiper/dist/idangerous.swiper.js']}},
//            swiper_hash:{files:{'<%= js_folder %>/idangerous.swiper.hashnav.js':['<%= dev_folder %>/swiper-hash-navigation/dist/idangerous.swiper.hashnav.js']}},
//            oridomi:{files:{'<%= js_folder %>/oridomi.js':['<%= dev_folder %>/oridomi/oridomi.js']}},
//            bg:{files:{'<%= js_folder %>/bgcheck.js':['<%= dev_folder %>/backgroundCheck/background-check.js']}},
//            swiper_progress:{files:{'<%= js_folder %>/vendor/swiper/swiper_progress.min.js':['<%= dev_folder %>/swiper/plugins/smooth-progress/idangerous.swiper.progress.js']}},
//            switchery:{files:{'<%= js_folder %>/vendor/switchery/switchery.min.js':['<%= dev_folder %>/switchery/dist/switchery.js']}},
//            mmenu:{files:{'<%= js_folder %>/vendor/mmenu/src/js/jquery.mmenu.min.all.js':['<%= dev_folder %>/jQuery.mmenu/src/js/jquery.mmenu.min.all.js']}},
        },


        jshint:{
            options:{
                smarttabs:true
            }
        },

        watch:{
            options:{
                livereload:true,
            },
            styles:{
                files:[
                    '<%= dev_folder %><%= dev_css %>/global.less',
                    '<%= dev_folder %><%= dev_css %>/*.less',
                    '<%= dev_folder %><%= dev_css %>/**/*.less',
                    '<%= dev_folder %>/bootstrap/less/*.less',
                ],
                tasks:[
                    'copy',
//                    'sass',
                    'less',
                ],
                options:{
                    nospawn:true
                }
            },
            scripts:{
                files:[
                    'gruntfile.js',
                    '<%= dev_folder %><%= dev_css %>/**/*.js',
                    '<%= dev_folder %>/**/*.js',
                ],
                tasks:[
                    'concat',
                    'copy',
                    'uglify',
//                    'less'
                ]
            }
        }

    });
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-concat');

//    grunt.loadNpmTasks('grunt-contrib-compress');
//    grunt.loadNpmTasks('grunt-contrib-requirejs');
//    grunt.loadNpmTasks("grunt-modernizr");
//    grunt.loadNpmTasks('grunt-csso');
//    grunt.loadNpmTasks('grunt-jslint');
//    grunt.loadNpmTasks('grunt-contrib-jshint');

    grunt.registerTask('default', [
        'copy',
//        'sass',
        'concat',
        'less',
        'uglify',
//        'watch'
    ]);
};