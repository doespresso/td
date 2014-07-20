"use strict";
console.log("ENTER");
if(Modernizr.webgl){
    console.log("wgl");
}


$script.path('../assets/js/');

$script('jquery.min', function() {
    console.log("jquery loaded");

    $script('bootstrap.min', function() {
        console.log("bootstrap loaded");

        $script('swiper.min', function() {
            console.log("swiper loaded");
        });

    });

    $script('underscore.min', function() {
        console.log("underscore loaded");

    });

    $script('snap.svg.min', function() {
        console.log("underscore loaded");

    });

});


