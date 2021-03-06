// Global function
function makeEqualHeigth(item1, item2) {
    var maxHeight = item1.height() > item2.height() ? item1.height() : item2.height();
    item1.height(maxHeight);
    item2.height(maxHeight);
}

// Make equal all items in two columns
function makeColumnsEqualHeight(column1Selector, column2Selector) {
    var column1Items = $(column1Selector);
    var column2Items = $(column2Selector);
    var minLength = column1Items.length < column2Items.length ? column1Items.length : column2Items.length;
    for (var i = 0; i < minLength; i++) {
        makeEqualHeigth($(column1Items[i]), $(column2Items[i]));
    }
}

// count view - TrieuNT
var countView = (function () {
    function updateView(type, id) {
        var i = new Image(1, 1);
        i.src = "/lg.gif?type=" + type + "&id=" + id;
        i.onload = function () {
            // Do nothing
        };
    }

    return {
        update: updateView
    }
})();

// Common init
$(window).on("load", function () {
    // make equal height two columns
    makeColumnsEqualHeight('#pnlCarsReview li', '#pnlBikesReview li');
    //$('#pnlCarsReview1,#pnlBikesReview1').equalHeight();

    // Get more lastest posts in home
    $('#btnLastestMore').click(function () {
        var me = $(this);
        var postUrl = me.data('url');
        var loadMoreToken = me.attr('data-token');
        me.hide();
        $('#imgMoreLoading').removeClass('hidden');
        $.get(postUrl, {'loadMoreToken': loadMoreToken}, function (responeData) {
            if (responeData && responeData.success == 1) {
                $('#pnlPostList').append(responeData.data);
                if (responeData.loadMoreToken != null) {
                    $('#imgMoreLoading').addClass('hidden');
                    me.show();
                    me.attr('data-token', responeData.loadMoreToken);
                } else{
                    $('#imgMoreLoading').remove();
                    return;
                }
            }
            else {
                $('#imgMoreLoading').addClass('hidden');
                me.show();
            }
        });
    });

    // Home menu hover
    var menuHoverTimer = 0;
    var currentMenu = '';
    $('#menu-bar li.menu-full, #menu-bar li.menu-box').hover(function () {
        var menu = '#' + $(this).attr('rel');
        if (menu == currentMenu) return;
        clearTimeout(menuHoverTimer);
        $(currentMenu).hide();
        currentMenu = menu;
        $(currentMenu).toggle();
    }, function () {
        menuHoverTimer = setTimeout(function () {
            $(currentMenu).hide();
            currentMenu = '';
        }, 300);
    })

    // click show menu mobile
    $('#event-menu').on('click', function () {
        var me = $(this);
        if (me.hasClass('active')) {
            me.removeClass('active');
            $('.menu-mobile').hide();
            me.children('.icon-menu').show();
            me.children('.icon-cancel').hide();

            $('html, body').css({
                overflow: 'auto',
                height: 'auto'
            });
        } else {
            me.addClass('active');
            $('.menu-mobile').show();
            me.children('.icon-menu').hide();
            me.children('.icon-cancel').show();

            $('#event-search').removeClass('active');
            $('.search-mobile').slideUp();
            $('#event-search').children('.icon-search').show();
            $('#event-search').children('.icon-cancel').hide();

            $('html, body').css({
                overflow: 'hidden',
                height: '100%'
            });
        }
    });

    // click show search mobile
    $('#event-search').on('click', function () {
        var me = $(this);
        if (me.hasClass('active')) {
            me.removeClass('active');
            $('.search-mobile').slideUp();
            me.children('.icon-search').show();
            me.children('.icon-cancel').hide();
        } else {
            me.addClass('active');
            $('.search-mobile').slideDown();
            me.children('.icon-search').hide();
            me.children('.icon-cancel').show();

            $('#event-menu').removeClass('active');
            $('.menu-mobile').hide();
            $('#event-menu').children('.icon-menu').show();
            $('#event-menu').children('.icon-cancel').hide();
        }
    });

    var prevScrollpos = window.pageYOffset;
    window.onscroll = function () {
        var currentScrollPos = window.pageYOffset;
        if (prevScrollpos > currentScrollPos) {
            document.getElementById("header").style.top = "0";
        } else if (currentScrollPos > 200) {
            document.getElementById("header").style.top = "-50px";
        } else {
            document.getElementById("header").style.top = "0";
        }
        prevScrollpos = currentScrollPos;
    }
});
