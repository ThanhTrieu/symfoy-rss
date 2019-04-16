$(function () {
    if(document.getElementById('avatarUrlVideos')) {
        var playerInstance = jwplayer("mainPlayVideo");
        var firstVideoMobile = document.getElementById('avatarUrlVideos').value;
        var checkLoadAjax = document.getElementById('urlAjaxLoadMore');
        var urlAjaxLoadMore = (checkLoadAjax) ? checkLoadAjax.value : '';

        function initVideos(type) {
            playerInstance.setup({
                file: getUrlVideo(),
                image: (type) ? getAvatarVideo() : firstVideoMobile,
                width: "100%",
                aspectratio: "16:9",
                autostart: (type) ? true : false,
                mute: false,
            });
        }

        function getAvatarVideo() {
            var avatar = $('#avatarUrlVideos').val().trim();
            return avatar;
        }

        function getUrlVideo() {
            var url = $('#hddUrlVideos').val().trim();
            var pos = url.indexOf('?');
            var paramsVideos = (pos == -1) ? "?showinfo=0&iv_load_policy=3&modestbranding=1&nologo=1&autoplay=0&controls=0&showtitle=0&cc_load_policy=1&loop=1" : "&showinfo=0&iv_load_policy=3&modestbranding=1&nologo=1&autoplay=0&controls=0&showtitle=0&cc_load_policy=1&loop=1";
            var fullUrl = url + paramsVideos;
            return fullUrl;
        }

        function fixCssVideo() {
            $('#mainPlayVideo').css({float: 'left', width: '100%'});
        }

        function changeVideoByClick() {
            $('.iframetrack').click(function () {
                var ulrVideo = $(this).attr('rel');
                var detailLink = $(this).attr('data-url');
                var urlAvatar = $(this).find('img').attr('alt');
                var titleVideo = $(this).next().find('h3>a.name-pro').text() || $(this).text();
                var sapoVideos = $(this).next().find('p.sapoVideos').text() || $(this).parent().parent().find('p.sapoVideos').text();
                // load video
                $('#hddUrlVideos').val(ulrVideo);
                // load avatar
                $('#avatarUrlVideos').val(urlAvatar);
                initVideos(true);
                fixCssVideo();

                // change text content
                $('#firstTitle').text(titleVideo);
                $('#firstSapo').text(sapoVideos);
                $('html,body').animate({scrollTop: 0}, 'slow');
                window.history.pushState(null, null, detailLink);
                return false;
            });

            $('.iframetrackTitle').click(function () {
                var ulrVideo = $(this).parent().prev().attr('rel');
                var detailLink = $(this).parent().prev().attr('data-url');
                var urlAvatar = $(this).parent().prev().find('img').attr('alt');

                var titleVideo = $(this).find('a.name-pro').text();
                var sapoVideos = $(this).find('p.sapoVideos').text();
                // load video
                $('#hddUrlVideos').val(ulrVideo);
                // load avatar
                $('#avatarUrlVideos').val(urlAvatar);
                initVideos(true);
                fixCssVideo();

                // change text content
                $('#firstTitle').text(titleVideo);
                $('#firstSapo').text(sapoVideos);
                $('html,body').animate({scrollTop: 0}, 'slow');
                window.history.pushState(null, null, detailLink);
                return false;
            });
        }

        if (firstVideoMobile) {
            initVideos(false);
            fixCssVideo();
            changeVideoByClick();
        }

        if (urlAjaxLoadMore) {
            $('#loadPaing').click(function () {
                var currentPage = $('#loadPaing').attr('rel').trim();
                var $self = $(this);
                $.ajax({
                    url: urlAjaxLoadMore,
                    type: "POST",
                    data: {page: currentPage},
                    dataType: "html",
                    beforeSend: function () {
                        $self.hide();
                        $('#imgMoreLoading').show();
                    },
                    success: function (result) {
                        var res = $.parseJSON(result);
                        currentPage++;
                        $self.attr('rel', currentPage);
                        $('#videos-main').append(res.data);
                        $('#imgMoreLoading').hide();
                        if (res.lastestVideos) {
                            $self.show();
                        }
                    }
                });
            });
        }
    }
});
