$(document).ready(function () {
    var widthHotTrending = $('#swiper-wrapper').width();
    var widthDevice = $(window).width();
    swiperHotTrendingByDevice(widthHotTrending, widthDevice);

    $('#image-slide').lightSlider({
        item: 1,
        speed: 500,
        controls: false,
        enableTouch: true,
        enableDrag:true,
        loop:true,
    });
    $('div.banner ul#image-slide li').click(function () {
       var link = $(this).find('a').attr('href').trim();
       window.location.href = link;
    });

    // su kien chuyen kich thuoc man hinh
    window.addEventListener("orientationchange", function() {
        var widthPhone = screen.width;
        swiperHotTrendingChangeScreen(widthHotTrending, widthPhone);
    }, false);


    $('.cars-review-posts').lightSlider({
        autoWidth:true,
        loop:true,
        enableDrag: true,
        slideMargin:10,
        adaptiveHeight:true,
        onSliderLoad: function() {
            $('.cars-review-posts').removeClass('cS-hidden');
        }
    });


    function swiperHotTrendingByDevice(wTrending, wMobile) {
        if(wTrending > wMobile) {
            $('#swiper-wrapper').lightSlider({
                autoWidth: true,
                loop: false,
                enableDrag: true,
                enableTouch: true,
                slideMargin: 10,
                adaptiveHeight: true,
                controls: false,
                pager: false,
                responsive: true,
                onSliderLoad: function () {
                    $('#swiper-wrapper').removeClass('cS-hidden');
                }
            });
        } else {
            $('#swiper-wrapper').removeClass('cS-hidden');
        }
    }


    function swiperHotTrendingChangeScreen(wTrending, wMobile) {
        if(wTrending > wMobile) {
            $('#swiper-wrapper').lightSlider({
                autoWidth: true,
                loop: false,
                enableDrag: true,
                enableTouch: true,
                slideMargin: 10,
                adaptiveHeight: true,
                controls: false,
                pager: false,
                responsive: true,
                onSliderLoad: function () {
                    $('#swiper-wrapper').removeClass('cS-hidden');
                }
            });
        } else {
            $('#swiper-wrapper').lightSlider({
                autoWidth: false,
                loop: false,
                enableDrag: true,
                enableTouch: true,
                slideMargin: 10,
                adaptiveHeight: true,
                controls: false,
                pager: false,
                responsive: true,
                onSliderLoad: function () {
                    $('#swiper-wrapper').removeClass('cS-hidden');
                }
            });
        }
    }
});
