//paceOptions = {
//    ajax: true,
//    restartOnRequestAfter: true,
//    restartOnPushState: true
//};

var sliding_speed = 1500,
    sl_progress = true;

//yepnope.injectCss(['dev/component/odometer/themes/odometer-theme-minimal.css']);
if (Modernizr.touch) {
    sliding_speed = 500;
//    sl_progress = false;
}

yepnope({
    test: Modernizr.touch,
    yep: 'assets/js/app/fastclick.js',
    callback: function (url, result, key) {
        FastClick.attach(document.body);
        console.log("load fastclick 1");
    },
    complete: function () {
    }
});

yepnope([
    {
        load: {
//            'pace':'assets/js/app/pace.js',
            'jquery': 'assets/js/app/jquery.js',
            'jaddress': 'assets/js/app/jquery.address.js',
//            'lazyload': 'assets/js/app/lazyload.js',
//            'fastclick':'assets/js/app/fastclick.js',
            'bootstrap': 'assets/js/app/bootstrap.js',
            'swiper': 'assets/js/app/swiper.js',
//            'swiper_hash': 'assets/js/app/swiper-hash.js',
            'swiper_progress': 'assets/js/app/swiper-progress.js',
//            'skrollr':'assets/js/app/skrollr.js',
//            'wow': 'assets/js/app/wow.js',
//            'lightbox': 'assets/js/app/magnific-popup.js',
        },
        callback: {
//            'pace':function (url, result, key) {            },
            'jquery': function (url, result, key) {

                $(document).ready(function () {
                    $(".section-wrapper").css('height', $(window).height() + 'px');
                });
                $(window).resize(function () {
//                    $(".page-slide,.sector .section-wrapper").css('height', $(window).height() + 'px');
                });

                $(function() {
                    $('a[href*=#]:not([href=#])').click(function() {
                        if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
                            var target = $(this.hash);
                            target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
//                            return false;
//                            if (target.length) {
//                                $('html,body').animate({
//                                    scrollTop: target.offset().top
//                                }, 1000);
                                return false;
//                            }
                        }
                    });

                    function changebg(img,mutator_class){
                        if (mutator_class != undefined) {
                            $("body").addClass(mutator_class);
                        }
                        else
                        {
                            $("body").removeClass('mutator-bg-dark');
                        }
                        $("#slide-bg").css({
                            "background-image":"url("+img+")",
                        });
                    }

                    $('a.load-gallery').on("click", function (e) {
                        e.preventDefault();
                        var gal_id = $(this).attr("data-attr-galid"),
                            target_id = $(this).attr("data-attr-target");
                        if (!(target_id === undefined || target_id === '')) {
                            $("#" + target_id).load(
                                "photos/gal/" + gal_id,
                                function (response, status, xhr) {
                                    if (status == "success") {
                                        console.log("sdlfkhadsfohasdkjfhlsadf");
                                        $("#" + target_id).show();

                                        var photos = $(".photo-item img");
                                        photos.each(
                                            function (index) {
                                                var src = $(this).parent().attr("href") || undefined;
                                                $(this).load(
                                                    src,
                                                    function (response, status, xhr) {
                                                        console.log(status);
                                                        var pself = this,
                                                            psrc = src;
                                                        setTimeout(function () {
                                                            $(pself).attr("src", psrc);
//                                        $("#slide-bg").css("background-image","url('http://localhost/img/bigpic.jpg')");
                                                            $(pself).parent().addClass('loaded')
                                                        }, 1500);


                                                    }
                                                );

                                            }
                                        );

                                        $("[rel=changebg]").on("click", function (e) {
                                            e.preventDefault();
                                            changebg($(this).attr("href"), $(this).attr("data-attr-colormutator"));
                                        });
                                    }
                                }
                            );
                        }

                    });




                });

                $("[rel=changebg]").on("click",function(e){
                    e.preventDefault();
                    $("body").addClass("mutator-bg-dark");
                    $("#slide-bg").css({
                        "background-image":"url("+$(this).attr("href")+")",
//                        "opacity":"0.9"
                    });
                });

                function menuclose() {
                    $("body").removeClass("menu-slide-open");
                }

                $("#menu-opener").on("click", function (e) {
                    console.log("menu");
                    $("body").addClass("menu-slide-open");
                });

                $("#menu-closer").on("click", function (e) {
                    console.log("menu close");
                    menuclose();
                });

                $("[rel='menu-div-open']").on("click", function (e) {
                    e.preventDefault();
                    $("body").addClass("show-overlay");
                    $(".menuwrap").delay(200).fadeIn(500);
                });

                $(".bgwrap").on("click", function (e) {
                    console.log("click");
                    e.preventDefault();
                    $("body").removeClass("show-overlay");
                    $(".menuwrap").delay(200).fadeOut(300);
                });

                $("#menu-slide li>a").on("click", function (e) {
                    menuclose();
                });

                $(".event").each(function () {
                    var event_bg = $(this).attr("data-attr-img");
                    var event_layer = "<figure class='bgitem' id='" + $(this).attr("id") + "bg' style='background-image:url(" + event_bg + "); z-ndex:" + ( 0 - $(this).attr("id")) + "'></figure>";
                    $('#image-bg').append(event_layer);
                });

                var timer;
                var timer1;
                var current_event = false;
                var te = 1000;
                var teo = 1000;

                $(".event").on("mouseenter", function (e) {
                    var self = this;
                    if (timer) window.clearTimeout(timer);

                    var timer = window.setTimeout(function () {
                        te = 1;
                        var current_event_id = $(self).attr('id');
                        $(self).addClass("active");
                        $("#" + current_event_id + "bg").addClass("active");
                    }, 600);
                });

                $(".event").on("mouseleave", function (e) {
                    if (timer) window.clearTimeout(timer);
                    var self = this;
                    var current_event_id = $(self).attr('id');
                    var timer = window.setTimeout(function () {
                        $(self).removeClass("active");
                        $("#" + current_event_id + "bg").removeClass("active");
                    }, 600);
                });

//                $("#menu-navigator").on('click', function (e) {
//                    e.preventDefault();
//                    console.log($(this).attr("data-attr-target"));
//                    return false;
//                });
            },
            'jaddress': function (url, result, key) {
                console.log('address');
                $.address.change(function(event) {
                    console.log(event.value);
                });
            },
//            'skrollr':function (url, result, key) {
//                (function ($) {
//                    // Init Skrollr
//                    var s = skrollr.init({
//                        render:function (data) {
//                            //Debugging - Log the current scroll position.
////                            console.log(data.curTop);
//                        }
//                    });
//                })(jQuery);
//            },

//
//            'wow': function (url, result, key) {
//                console.log("wow");
//                new WOW().init();
//            },


//            'lazyload':function (url, result, key) {
//                console.log("lazyload");
//                $("img.lazy").lazyload();
//            },


            'bootstrap': function (url, result, key) {
            },
//            'espy':function (url, result, key) {
//                console.log("elspy");
//                $("section").espy(function(entered, state){
//                    if (entered){
////                        setTimeout(function(){
//                                $(this).find(".show-me").addClass("show-on bounce");
////                                $(this).css("background-color","red");
////                            },1000);
//                        console.log("section----enter",$(this).attr("id"));
//                    }
//                },{
//                    offset:-200
//                });
////                $("#top-wrapper").espy(function(entered, state){
////                    if (entered){
//                        setTimeout(function () {
//                            $("#cranes-count").fadeIn();
//                            $("#top-wrapper .rus").fadeIn();
//                            $('#cranes-count').html('745');
////                            od.update(752);
//                        }, 2000);
////                    }
////                },{
////                    offset:-100
////                });
//            },

//            'odometer':function (url, result, key) {
//                console.log('odometer');
//                if ($('body').find('.odometer').length > 0) {
//                    od = new Odometer({
//                        el:$('#cranes-count')[0],
//                        duration:300,
//                        format:'',
//                        theme:'minimal',
//                        animation:'simple'
//                    });
//                    setTimeout(function () {
//                        $('#cranes-count').html('200');
//                        $('#cranes-count').fadeIn(200, function () {
//
//
//                        });
//                        $('#for-counter').fadeIn(1000);
//
//                    }, 500);
//                }
//            },

//            'switchery':function (url, result, key) {
//                console.log("switch");
//                var swelem = document.querySelector('#switcher1');
//                var switcher = new Switchery(swelem);
//            },

//            'lightbox':function (url, result, key) {
//                $('#gallery').magnificPopup({
//                    delegate:'a',
//                    type:'image',
//                    gallery:{
//                        enabled:true,
//                        preload:[0, 2],
//                        navigateByImgClick:true,
//                        arrowMarkup:'<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',
//                        tPrev:'назад',
//                        tNext:'вперед',
//                        tCounter:'<span>%curr% из %total%</span>'
//                    },
//                    image:{
//                        titleSrc:'title'
//                    },
//                    mainClass:'mfp-fade',
//                    removalDelay:300,
//                });
//                $('.zoom').magnificPopup({
//                    type:'image',
//                    image:{
//                        titleSrc:'title'
//                    },
//                    mainClass:'mfp-fade',
//                    removalDelay:300,
//                });
//            },

            'swiper': function (url, result, key) {



            },

            'swiper_progress': function (url, result, key) {

                $(document).ready(function ($) {


                    var menunav = new Swiper('#navmenus', {
                        slidesPerView:'auto',
                        calculateHeight:true,
                        cssWidthAndHeight:false,
//                        centeredSlides:true,

//                        paginationClickable: false,
//                        pagination: '#navmenus-pager',
                    });

//                    var pages = new Swiper('#pages', {
//                        mode:'vertical',
//                        mousewheelControl:true,
//                        speed: 240,
//                        slideClass:'page-slide',
//                    });




                    var sw = new Swiper('#main', {
                        watchActiveIndex: true,
                        centeredSlides: false,
                        resizeReInit: true,
                        speed: sliding_speed,
                        followFinger: true,
                        paginationClickable: true,
                        pagination: '#presentation-pager',
                        autoplay: 5000,
                        loop: true,
                        progress: sl_progress,
                        onFirstInit: function (swiper) {
                            $("body").removeClass("loading");
                            setTimeout(function(){$("#main-loader").css("display","none")},2000);
                        },
                        onInit: function (swiper) {
                            console.log("gen init");
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
                        onSlideChangeEnd: function (swiper, direction) {
                            console.log(direction);
                            $(sw.activeSlide()).find('.title').addClass('animated');
                        }
                    });

                    $("#presentation .next").on("click", function (event) {
                        event.preventDefault();
                        sw.swipeNext();
                    });
                    $("#presentation .prev").on("click", function (event) {
                        event.preventDefault();
                        sw.swipePrev();
                    });


                    ////////////
                    var sm = new Swiper('#menus', {
                        watchActiveIndex: true,
                        centeredSlides: false,
                        resizeReInit: true,
                        speed: sliding_speed,
                        followFinger: true,
//                        paginationClickable: true,
//                        pagination: '#presentation-pager',
//                        autoplay: 5000,
                        loop: true,
                        progress: sl_progress,
//                        onFirstInit: function (swiper) {
//                            $("#presentation .slides-wrapper").removeClass('loading-state');
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
                    });
                    ////////
                    var sp = new Swiper('#photos_nav', {
                        slidesPerView:'auto',
                        watchActiveIndex: true,
                        cssWidthAndHeight:true,
                        speed: sliding_speed,
                        followFinger: true,
                        loop: false,
                        onInit: function (swiper) {
                            console.log("p init");
                        },
                    });

                    $("#gallery_terrace .next").on("click", function (event) {
                        event.preventDefault();
                        sp.swipeNext();
                    });
                    $("#gallery_terrace .prev").on("click", function (event) {
                        event.preventDefault();
                        sp.swipePrev();

                    });

                    ////////////



                    ////////////




//                    var sm = new Swiper('#menu-slider', {
//
//                        watchActiveIndex:true,
//                        centeredSlides:false,
//                        resizeReInit:true,
//                        followFinger:true,
//                        longSwipesRatio:0.2,
//                        touchRatio:0.5,
//                        shortSwipes:true,
//                        speed:100,
//                        loop:true,
//                        mode:'horizontal',
//                        calculateHeight:true,
//                        keyboardControl:true,
//                        autoplayDisableOnInteraction:true,
//                        onSlideChangeStart: function(){
//                          $("#menu-selector a.active").removeClass('active');
//                          $("#menu-selector a").eq(sm.activeIndex - 1).addClass('active');
//                        }
//                    });

//                    $("#menu-selector a").on('click', function (event) {
//                        event.preventDefault();
//                        sm.swipeTo($("#menu-selector a").index(this));
//                        return false;
//                    });

                });
            },


//            'swiper_hash': function (url, result, key) {
//
//                console.log("swiper hash");
//
//            },

//            'sld': function (url, result, key) {
//
//            },

//            'background-check':function (url, result, key) {
//
//                    BackgroundCheck.init({
//                      targets: '.target',
//                      images: '.swiper-slide'
//                    });
//
//            },
        }
    }

]


);