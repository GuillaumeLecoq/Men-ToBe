$(document).ready(function() {
    'use strict';

    /** HOMEPAGE CAROUSEL **/
    if ($(".featured-slider").length) {
        $(".featured-slider").owlCarousel({
            autoPlay: 3000,
            items: 2,
            pagination: false,
            itemsMobile: [768, 1],
            itemsDesktop: [1199, 2],
            itemsDesktopSmall: [979, 1]
        });
    }
});

