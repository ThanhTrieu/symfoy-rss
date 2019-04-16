$(document).ready(function () {
    var postId = $('#dataPostId').val();
    if (postId) {
        countView.update(1, postId);
        $('div.new-detail ul#image-gallery').lightSlider({
            gallery: true,
            item: 1,
            thumbItem: 4,
            slideMargin: 0,
            speed: 1000,
            auto: true,
            loop: true,
            caption: true,
            captionLink: true,
            onSliderLoad: function (el) {
                $('div.new-detail ul#image-gallery').removeClass('cS-hidden');
                el.lightGallery({
                    selector: 'div.new-detail ul#image-gallery .lslide'
                });
            }
        });
        $('.text-title-cates').each(function () {
            var el = $(this);
            var divHeight = el.outerHeight();
            var lineHeight = parseInt(el.css('line-height').replace('px', ''));
            var lines = Math.ceil(divHeight / lineHeight);
            var str = $.trim($(el).find('a').text());
            if (lines > 3) {
                var textData = str.substring(0, 45) + "...";
                $(el.find('a')).html(textData);
            }
        });

        $('.text-title-tags').each(function () {
            var el = $(this);
            var divHeight = el.outerHeight();
            var lineHeight = parseInt(el.css('line-height').replace('px', ''));
            var lines = Math.ceil(divHeight / lineHeight);
            var str = $.trim($(el).find('a').text());
            if (lines > 3) {
                var textData = str.substring(0, 45) + "...";
                $(el.find('a')).html(textData);
            }
        });
    }
});