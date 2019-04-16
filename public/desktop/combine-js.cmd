uglifyjs ^
js/jquery-3.3.1.min.js ^
js/main.js ^
js/search/search.js ^
js/galleries/lightslider.js ^
js/galleries/popup_photo.js ^
js/lightbox/picturefill.min.js ^
js/lightbox/lightgallery-all.min.js ^
js/lightbox/jquery.mousewheel.min.js ^
js/lightbox/detail-post.js ^
jwplayer/7.12.2/jwplayer.js ^
js/galleries/video.js ^
--compress sequences=true,dead_code=true,conditionals=true,booleans=true,unused=true,if_return=true,join_vars=true,drop_console=true --mangle ^
--output js/iab.min.js