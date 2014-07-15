console.log("init");

require.config = {
    baseUrl:"assets/js/app",
    waitSeconds:15,
    paths:{
        "jquery":"jquery",
        "swiper":"swiper",
        "oridomi":"oridomi",
    },
    shim:{
        'jquery':{
            exports:'$'
        },
        'swiper':{
            deps:['jquery'],
            exports:'Swiper'
        },
        'oridomi':{
            deps:['jquery'],
            exports:'oriDomi'
        },
    }
};

require(['jquery','swiper','oridomi'], function ($, jquery, swiper, oriDomi) {
    "use strict";
    $(document).ready(function ($) {
        console.log('ready swiper 4');
        var sw = new Swiper('#main', {
//            slidesPerView:'auto',
            watchActiveIndex:true,
            centeredSlides:false,
            speed:2000,
            loop:true,
            mode:'horizontal',
//            calculateHeight:true,
            mousewheelControl:false,
            keyboardControl:true,
            paginationClickable:true,
            pagination:'#presentation-pager',
            autoplay:5000,
        });
    });
    var $folded = $('#welcome').oriDomi();
    $folded.curl(50).collapse().setSpeed(2000).stairs(-29).foldUp().unfold();
});
