var scrollTabLeftSocail = function () {
    function init() {
        $(window).scroll(function () {
            var windowTop = $(window).scrollTop();
            var distanceNews = $('.list-car-left').offset().top;
            var distanceFooter = $('.like').offset().top;
            var heightFb = $('.fb-detail').height();

            if (windowTop < distanceNews) {
                $('.fb-detail').removeAttr('style');
                $('.new-detail').removeAttr('style');
            }
            else if (windowTop > distanceNews && windowTop < (distanceFooter - (distanceNews + 150))) {
                $('.fb-detail').css({
                    position: 'fixed',
                    top: '150px'
                });
                $('.new-detail').css('padding-left', '98px');
            } else if (windowTop > (distanceFooter - (distanceNews + 150))) {
                $('.fb-detail').css({
                    position: 'relative',
                    top: distanceFooter - (distanceNews + heightFb + 370)
                });
                $('.new-detail').removeAttr('style');
            }
        });
    }

    return {
        init: init
    };
}();

$(document).ready(function () {
    var postId = $('#dataPostId').val();
    var urlShareLink = $('#dataUrlShareLink').val();
    if (postId) {
        scrollTabLeftSocail.init();
        countView.update(1, postId);
        $('#lightgallery').lightGallery();
        $('#btn-share').on('click', function (e) {
            e.preventDefault();
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + urlShareLink, 'popupwindow', 'scrollbars=yes,width=600,height=580');
        });
    }
    /*share*/
    window.fbAsyncInit = function () {
        FB.init({
            appId: facebookAppId,
            status: true,
            xfbml: true,
            version: 'v3.2'
        });
        // share FB
        window.facebookShare = function (callback) {
            var options = ({
                method: 'share',
                href: urlShareLink
            });
            var status = '';
            FB.ui(options, function (response) {
                if (response && !response.error_code) {
                    status = 'success';
                    $.event.trigger('fb-share.success');

                } else {
                    status = 'error';
                    $.event.trigger('fb-share.error');
                }
                if (callback && typeof callback === "function") {
                    callback.call(this, status);
                } else {
                    return response;
                }
            });
        }
    };
});