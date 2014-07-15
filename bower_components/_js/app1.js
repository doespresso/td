var sliding_speed = 2000,
    sl_progress = true,
    short = false,
    reinittimeout;

//yepnope.injectCss(['dev/component/odometer/themes/odometer-theme-minimal.css']);

if (Modernizr.touch) {
    sliding_speed = 500;
    short = true;
    console.log("short");
//    sl_progress = false;
}

//yepnope({
//    test: Modernizr.touch,
//    yep: 'assets/js/app/fastclick.js',
//    callback: function (url, result, key) {
//        FastClick.attach(document.body);
//        console.log("load fastclick 1");
//    },
//    complete: function () {
//    }
//});

window.onload = function () {
    console.log('wl');
    if (location.hash) { // do the test straight away
        window.scrollTo(0, 0); // execute it straight away
        setTimeout(function () {
            window.scrollTo(0, 0); // run it a bit later also for browser compatibility
        }, 1);
    }
}




var c_page_id,
    c_page_index,
    l_page_id,
    l_page_index;


yepnope([
    {
        load: {
            'jquery': 'assets/js/app/jquery.js',
            'swiper': 'assets/js/app/swiper.js',
//            'swiper_hash': 'assets/js/app/swiper-hash.js',
            'swiper_progress': 'assets/js/app/swiper-progress.js',
        },
        callback: {
            'jquery': function (url, result, key) {




                /////

//                $(document).ready(function () {
//                    $(".section-wrapper").css('height', $(window).height() + 'px');
//                    console.log("LOAD");
//                });


                $(function () {
                    $('a[href*=#]:not([href=#])').click(function () {
                        if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
                            var target = $(this.hash);
                            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
                            return false;
                            if (target.length) {
                                $('html,body').animate({
                                    scrollTop: target.offset().top
                                }, 1000);
                                return false;
                            }
                        }
                    });

                    function changebg(img, mutator_class) {
                        if (mutator_class != undefined) {
                            $("body").addClass(mutator_class);
                        }
                        else {
                            $("body").removeClass('mutator-bg-dark');
                        }
                        $("#slide-bg").css({
                            "background-image": "url(" + img + ")",
                        });
                    }

                    $('#menu-opener').on("click", function (e) {
                        console.log("open menu");
                        $(".overlay").addClass("open");
                    });

                    $('button.overlay-close').on("click", function (e) {
                        console.log("open menu");
                        $(".overlay").removeClass("open");
                    });


                });

                /////////


            },

            'swiper': function (url, result, key) {


            },

            'swiper_progress': function (url, result, key) {


//                function initpages(slider) {
//                    var loading_timer = setTimeout(function () {
//                        $("body").removeClass("loading");
//                        presentation.startAutoplay();
//                    }, 500);
//                    console.log(slider.slides.length, 'PAGES');
//                    var pages = slider.slides;
//                    var datagallery;
//                    pages.forEach(function (page) {
//                        datagallery = $(page).attr("data-photos");
//                        console.log(datagallery, "GALLERY");
//                        if (datagallery) {
//                            console.log("ok", datagallery);
//                            $(page).attr("data-photos-loaded", "1");
//                            $(page).find(".go-showcase").addClass("active");
//                        }
//                    });
//                }

//                var pages = new Swiper('#main-pages', {
//                    mode: 'vertical',
//                    noSwiping: true,
//                    mousewheelControl: true,
//                    speed: 500,
//                    resizeReInit: true,
//                    wrapperClass: 'pages-wrapper',
//                    slideClass: 'page-slide',
//                    onSwiperCreated: function (swiper) {
//                        initpages(swiper);
//                    }
//                });
//
//                pages.addCallback('SlideChangeStart', function (swiper) {
//                    $("#go-showcase").removeClass("active");
//                });
//                pages.addCallback('SlideChangeEnd', function (swiper) {
//                    c_page_id = $(pages.activeSlide()).attr("id");
//                    c_page_index = pages.activeIndex;
//                    l_page_index = pages.previousIndex;
//                    console.log(c_page_id, c_page_index, l_page_index);
////                    sub_pages.swipeTo(0);
//                    if (c_page_index !== 0) {
//                        presentation.stopAutoplay();
//                    } else {
//                        presentation.startAutoplay();
//                    }
//                    //////showcase
//                    if ($(swiper.activeSlide()).attr("data-photos")) {
//                        $("#go-showcase").addClass("active");
//                    } else {
//                    }
//                });
//                pages.addCallback('TouchStart', function (swiper) {
//                    console.log("touch");
//                });





                var presentation = new Swiper('#main-swiper', {
                    mode: 'horizontal',
//                    mousewheelControl: true,
//                    slidesPerView:'2',
                    slidesPerViewFit:false,
//                    loop:true,
//                    freeMode:true,
                    centeredSlides:true,
                    initialSlide:0,
                    speed: sliding_speed,
                    autoplay: 5000,
                    autoplayDisableOnInteraction: true,
                    shortSwipes:true,
                    offsetPxBefore:380,

//                    offsetSlidesBefore:1,
//                    offsetSlidesAfter:1,

                    resizeReInit: true, // на сколько я понимаю этот параметр не учитывается [1]
                    pagination:"#paging",
                    createPagination:true,
                    paginationClickable:true,
//                    progress: sl_progress,
                    onFirstInit:function(swiper){
                        console.log("first init");
                    },
                    onFirstInit:function(swiper){
                        setTimeout(function () {
                            $('#logo').removeClass("supersized");
                        }, 200);
                    },
                    onInit: function(swiper) {
//                        setTimeout(function(){
//                        for (var i = 0; i < swiper.slides.length; i++){
//                            swiper.setTransform(slide, 'translate3d(0,0,0)');
//                        }
//                        },1000);
//                        swiper.swipeTo(0,1);
//                        $(".swiper-slide:not(.swiper-slide-active)").on("click",function(e){
//                            console.log("2134454436");
//                        });
                        console.log("resize");
                    },
                    onSlideClick:function(swiper){
                        var act = swiper.clickedSlide;
                        console.log(act);
                        if (!act.isActive()){
                        swiper.swipeTo(swiper.clickedSlideIndex);
                        }
                    },
                    onSwiperCreated: function (swiper) {
//                        swiper.stopAutoplay();
                    },

                    onProgressChange: function (swiper) {
                        for (var i = 0; i < swiper.slides.length; i++) {
                            var slide = swiper.slides[i];
                            var progress = slide.progress;
                            var translate = progress * swiper.width;
                            var opacity = 1 - Math.min(Math.abs(progress), 1);
                            slide.style.opacity = opacity;
                            swiper.setTransform(slide, 'translate3d(' + translate + 'px,0,0)');
                        }
                    },
                    onTouchStart: function (swiper, speed) {
                        for (var i = 0; i < swiper.slides.length; i++) {
                            swiper.setTransition(swiper.slides[i], 0);
                        }
                    },
                    onSetWrapperTransition: function (swiper, speed) {
                        for (var i = 0; i < swiper.slides.length; i++) {
                            swiper.setTransition(swiper.slides[i], speed);
                        }
                    },
                });


                function resetprogress() {
                    console.log("DO IT");
                    presentation.reInit();
                }

                $("[data-action=reset]").on("click", function (e) {
                    // что интересно здесь (по нажатию) "резет" происходит корректно
                    console.log("reset");
                    presentation.reInit();
                    presentation.swipeTo(0,1);

                });


//                var doit;
//                window.onresize = function() {
//                    clearTimeout(doit);
//                    doit = setTimeout(function() {
//                        resetprogress();
//                    }, 1000);
//                };
// внешний запуск также отрабатывает некорректно




//                $("#main-pages .pages-container").each(function () {
//                    _self = this;
//                    console.log("subslider");
//                    $(this).swiper({
//                        mode: 'horizontal',
//                        mousewheelControl: false,
//                        speed: 400,
//                        resizeReInit: true,
//                        wrapperClass: 'pages-wrapper',
//                        slideClass: 'page-slide',
//                        progress: true,
//                        onProgressChange: function (swiper) {
//                            for (var i = 0; i < swiper.slides.length; i++) {
//                                var slide = swiper.slides[i];
//                                var progress = slide.progress;
//                                var translate, boxShadow;
//                                if (progress > 0) {
//                                    translate = progress * swiper.width;
//                                    boxShadowOpacity = 0;
//                                }
//                                else {
//                                    translate = 0;
//                                    boxShadowOpacity = 1 - Math.min(Math.abs(progress), 0.5);
//                                }
////                                slide.style.boxShadow='0px 0px 10px rgba(0,0,0,'+boxShadowOpacity+')';
//                                swiper.setTransform(slide, 'translate3d(' + (translate) + 'px,0,0)');
//                            }
//                        },
//                        onTouchStart: function (swiper) {
//                            for (var i = 0; i < swiper.slides.length; i++) {
//                                swiper.setTransition(swiper.slides[i], 0);
//                            }
//                        },
//                        onSetWrapperTransition: function (swiper, speed) {
//                            for (var i = 0; i < swiper.slides.length; i++) {
//                                swiper.setTransition(swiper.slides[i], speed);
//                            }
//                        },
//                        onInit: function (swiper) {
//                            for (var i = 0; i < swiper.slides.length; i++) {
//                                swiper.slides[i].style.zIndex = i;
//                            }
//                        }
//                    });
//                });












                $("#go-page-down").on("click", function (e) {
                    e.preventDefault();
                    pages.swipeNext();
                })

//                    var sw = new Swiper('#main', {
//                        watchActiveIndex: true,
//                        centeredSlides: false,
//                        resizeReInit: true,
//                        speed: sliding_speed,
//                        followFinger: true,
//                        paginationClickable: true,
//                        pagination: '#presentation-pager',
//                        autoplay: 5000,
//                        loop: true,
//                        progress: sl_progress,
//                        onFirstInit: function (swiper) {
//                            $("body").removeClass("loading");
//                            setTimeout(function () {
//                                $("#main-loader").css("display", "none")
//                            }, 2000);
//                        },
//                        onInit: function (swiper) {
//                            console.log("gen init");
//                        },
//                        onProgressChange: function (swiper) {
//                            for (var i = 0; i < swiper.slides.length; i++) {
//                                var slide = swiper.slides[i];
//                                var progress = slide.progress;
//                                var translate = progress * swiper.width;
//                                var opacity = 1 - Math.min(Math.abs(progress), 1);
//                                slide.style.opacity = opacity;
//                                swiper.setTransform(slide, 'translate3d(' + translate + 'px,0,0)');
//                            }
//                        },
//                        onTouchStart: function (swiper, speed) {
//                            for (var i = 0; i < swiper.slides.length; i++) {
//                                swiper.setTransition(swiper.slides[i], 0);
//                            }
//                        },
//                        onSetWrapperTransition: function (swiper, speed) {
//                            for (var i = 0; i < swiper.slides.length; i++) {
//                                swiper.setTransition(swiper.slides[i], speed);
//                            }
//                        },
//                        onSlideChangeEnd: function (swiper, direction) {
//                            console.log(direction);
//                            $(sw.activeSlide()).find('.title').addClass('animated');
//                        }
//                    });
//                    $("#presentation .next").on("click", function (event) {
//                        event.preventDefault();
//                        sw.swipeNext();
//                    });
//                    $("#presentation .prev").on("click", function (event) {
//                        event.preventDefault();
//                        sw.swipePrev();
//                    });


            },
        }
    }
]

);