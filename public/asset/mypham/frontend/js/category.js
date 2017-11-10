function hover_menu_left() {
	var firstTime = true;
	var top = -1;

	var timer;
	var delay = 100;
	jQuery('.categories-main').hover(function() {
		var that = $(this);
		timer = setTimeout(function() {
			jQuery(that).addClass('active');
		}, delay);
	}, function() {
		jQuery(this).removeClass('active');
		clearTimeout(timer);
	});

	jQuery('.categories-list-box').hover(function() {
		$('#jsMenuMarkLayer').stop().delay(20).fadeIn(100);
	}, function() {
		$('#jsMenuMarkLayer').stop().delay(20).fadeOut(100);
	});
	$('.nav-main').menuAim({
		rowSelector: "li.menuItem",
		submenuDirection: "right",
		activate: function(a) {
			if (firstTime) {
				$(a).addClass('active').children('.sub-cate').css({
					width: '0',
					display: 'block'
				}).animate({
					width: '720px'
				}, 100);
			} else {
				$(a).addClass('active').children('.sub-cate').show();
			}
			var ind = $(a).index();
			/*
            for (var i = 0; i <= ind; i++) {
                $('.nav-main > li').eq(ind).find('div.sub-cate').css({'top': top + 'px'});
                top = top - 61;
            }*/
			firstTime = false;
			$("img.lazyMenu", $(a)).each(function() {
				$(this).attr("src", $(this).attr("data-original"));
				$(this).removeAttr("data-original");
			});
		},
		deactivate: function(a) {
			$(a).removeClass('active').children('.sub-cate').hide();
			//top = -1;
		},
		exitMenu: function() {
			firstTime = true;
			$('.sub-cate').hide();
			$('.nav-main-box > .nav-main > li').removeClass('active');
			// top = -1;
			return true;
		}
	});
}

function menuAimExit() {
	$('.sub-cate').hide();
	$('.nav-main-box > .nav-main > li').removeClass('active');
}

jQuery(document).ready(function($) {
	hover_menu_left();
})
