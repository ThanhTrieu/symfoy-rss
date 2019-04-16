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
                    //console.log(responeData.loadMoreToken);
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
});
