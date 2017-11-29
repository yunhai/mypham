// Biến khởi tạo
var view_collection = true;
var viewout = true;
var timeOut_modalCart;
var check_show_modal = true;
var timeOut_tabIndex;
var check_show_tabIndex = true;
var cur_scrollTop = 0;
if ( typeof(formatMoney) == 'undefined' ){
	formatMoney = '';
}
var check_header_fixTop = false;

// Keyup find item in list filter collection
function filterItemInList(object) {
	q = object.val().toLowerCase();
	object.parent().next().find('li').show();
	if (q.length > 0) {
		object.parent().next().find('li').each(function() {
			if ($(this).find('label').attr("data-filter").indexOf(q) == -1)
				$(this).hide();
		})
	}
}

// Check owl item next/prev show or hide
function checkItemOwlShow(object,tab,a,b,c,d) {
	if ( tab == 'tab' ) {
		item = object.find('.active').find('.owl-carousel');
	} else {
		item = object.find('.owl-carousel');
	}
	if ( item.find('.owl-item.active').length < a && $(window).width() >= 1200 ) {
		item.find('.owl-controls').hide();
	}
	if ( item.find('.owl-item.active').length < b && $(window).width() >= 992 && $(window).width() < 1199 ) {
		item.find('.owl-controls').hide();
	}
	if ( item.find('.owl-item.active').length < c && $(window).width() >= 768 && $(window).width() < 991 ) {
		item.find('.owl-controls').hide();
	}
	if ( item.find('.owl-item.active').length < d && $(window).width() < 768 ) {
		item.find('.owl-controls').hide();
	}
}

// Destroy resize image
function destroyResize(url){
	if ( url != undefined ) {
		if ( url.indexOf('_pico') != -1 || url.indexOf('_icon') != -1 || url.indexOf('_thumb') != -1
				|| url.indexOf('_small') != -1 || url.indexOf('_compact') != -1 || url.indexOf('_medium') != -1
				|| url.indexOf('_large') != -1 || url.indexOf('_grande') != -1 || url.indexOf('_1024x1024') != -1
				|| url.indexOf('_2048x2048') != -1 || url.indexOf('_master') != -1 ) {
			link_image = (url.split('_')[url.split('_').length - 1]).split('.')[0];
			switch (link_image) {
				case 'pico':
					link_image = url.split('_pico').join('').replace('http:','').replace('https:','');;
					break;
				case 'icon':
					link_image = url.split('_icon').join('').replace('http:','').replace('https:','');;
					break;
				case 'thumb':
					link_image = url.split('_thumb').join('').replace('http:','').replace('https:','');;
					break;
				case 'small':
					link_image = url.split('_small').join('').replace('http:','').replace('https:','');;
					break;
				case 'compact':
					link_image = url.split('_compact').join('').replace('http:','').replace('https:','');;
					break;
				case 'medium':
					link_image = url.split('_medium').join('').replace('http:','').replace('https:','');;
					break;
				case 'large':
					link_image = url.split('_large').join('').replace('http:','').replace('https:','');;
					break;
				case 'grande':
					link_image = url.split('_grande').join('').replace('http:','').replace('https:','');;
					break;
				case '1024x1024':
					link_image = url.split('_1024x1024').join('').replace('http:','').replace('https:','');;
					break;
				case '2048x2048':
					link_image = url.split('_2048x2048').join('').replace('http:','').replace('https:','');;
					break;
				case 'master':
					link_image = url.split('_master').join('').replace('http:','').replace('https:','');;
					break;
			}
			return link_image;
		}
		return url;
	}
}

// Modal Cart
function getCartModal(){
	var cart = null;
	jQuery('#cartform').hide();
	jQuery('#myCart #exampleModalLabel').text("Giỏ hàng");
	jQuery.getJSON('/cart.js', function(cart, textStatus) {
		if(cart)
		{
			jQuery('#cartform').show();
			jQuery('.line-item:not(.original)').remove();
			jQuery.each(cart.items,function(i,item){
				var total_line = 0;
				var total_line = item.quantity * item.price;
				tr = jQuery('.original').clone().removeClass('original').appendTo('table#cart-table tbody');
				if(item.image != null)
					tr.find('.item-image').html("<img src=" + Haravan.resizeImage(item.image,'small') + ">");
				else
					tr.find('.item-image').html("<img src='//hstatic.net/0/0/global/noDefaultImage6_large.gif'>");
				vt = item.variant_options;
				if(vt.indexOf('Default Title') != -1)
					vt = '';
				tr.find('.item-title').children('a').html(item.product_title + '<br><span>' + vt + '</span>').attr('href', item.url);
				tr.find('.item-quantity').html("<input id='quantity1' name='updates[]' min='1' type='number' value=" + item.quantity + " class='' />");
				if ( typeof(formatMoney) != 'undefined' ){
					tr.find('.item-price').html(Haravan.formatMoney(total_line, formatMoney));
				}else {
					tr.find('.item-price').html(Haravan.formatMoney(total_line, ''));
				}
				tr.find('.item-delete').html("<a href='javascript:void(0);' onclick='deleteCart(" + item.variant_id + ")' ><i class='fa fa-times'></i></a>");
			});
			jQuery('.item-total').html(Haravan.formatMoney(cart.total_price, formatMoney));
			jQuery('.modal-title').children('b').html(cart.item_count);
			jQuery('.cart-number').html(cart.item_count );
			if(cart.item_count == 0){
				jQuery('#exampleModalLabel').html('Giỏ hàng của bạn đang trống. Mời bạn tiếp tục mua hàng.');
				jQuery('#cart-view').html('<tr><td>Hiện chưa có sản phẩm</td></tr>');
				jQuery('#cartform').hide();
			}
			else{
				jQuery('#exampleModalLabel').html('Bạn có ' + cart.item_count + ' sản phẩm trong giỏ hàng.');
				jQuery('#cartform').removeClass('hidden');
				jQuery('#cart-view').html('');
			}
			if ( jQuery('#cart-pos-product').length > 0 ) {
				jQuery('#cart-pos-product span').html(cart.item_count + ' sản phẩm');
			}
			// Get product for cart view

			jQuery.each(cart.items,function(i,item){
				clone_item(item);
			});
			jQuery('#total-view-cart').html(Haravan.formatMoney(cart.total_price, formatMoney));
		}
		else{
			jQuery('#exampleModalLabel').html('Giỏ hàng của bạn đang trống. Mời bạn tiếp tục mua hàng.');
			if ( jQuery('#cart-pos-product').length > 0 ) {
				jQuery('#cart-pos-product span').html(cart.item_count + ' sản phẩm');
			}
			jQuery('#cart-view').html('<tr><td>Hiện chưa có sản phẩm</td></tr>');
			jQuery('#cartform').hide();
		}
	});
}

function clone_item(product){
	var item_product = jQuery('#clone-item-cart').find('.item_2');
	item_product.find('img').attr('src',Haravan.resizeImage(product.image,'small')).attr('alt', product.url);
	item_product.find('a').attr('href', product.url).attr('title', product.url);
	item_product.find('.pro-title-view').html(product.title);
	item_product.find('.pro-quantity-view').html('Số lượng: ' + product.quantity);
	item_product.find('.pro-price-view').html('Giá: ' + Haravan.formatMoney(product.price,formatMoney));
	item_product.clone().removeClass('hidden').prependTo('#cart-view');
}

// Delete variant in modalCart
function deleteCart(variant_id){
	var params = {
		type: 'POST',
		url: '/cart/change.js',
		data: 'quantity=0&id=' + variant_id,
		dataType: 'json',
		success: function(cart) {
			getCartModal();
		},
		error: function(XMLHttpRequest, textStatus) {
			Haravan.onError(XMLHttpRequest, textStatus);
		}
	};
	jQuery.ajax(params);
}

// Update product in modalCart
jQuery(document).on("click","#update-cart-modal",function(event){
	event.preventDefault();
	if (jQuery('#cartform').serialize().length <= 5) return;
	jQuery(this).html('Đang cập nhật');
	var params = {
		type: 'POST',
		url: '/cart/update.js',
		data: jQuery('#cartform').serialize(),
		dataType: 'json',
		success: function(cart) {
			if ((typeof callback) === 'function') {
				callback(cart);
			} else {
				getCartModal();
			}
			jQuery('#update-cart-modal').html('Cập nhật');
		},
		error: function(XMLHttpRequest, textStatus) {
			Haravan.onError(XMLHttpRequest, textStatus);
		}
	};
	jQuery.ajax(params);
});

// Add a product in checkout
var buy_now = function(id) {
	var quantity = 1;
	var params = {
		type: 'POST',
		url: '/cart/add.js',
		data: 'quantity=' + quantity + '&id=' + id,
		dataType: 'json',
		success: function(line_item) {
			window.location = '/checkout';
		},
		error: function(XMLHttpRequest, textStatus) {
			Haravan.onError(XMLHttpRequest, textStatus);
		}
	};
	jQuery.ajax(params);
}

// Add a product in cart
var add_item = function(id) {
	var quantity = 1;
	var params = {
		type: 'POST',
		url: '/cart/add.js',
		data: 'quantity=' + quantity + '&id=' + id,
		dataType: 'json',
		success: function(line_item) {
			window.location = '/cart';
		},
		error: function(XMLHttpRequest, textStatus) {
			Haravan.onError(XMLHttpRequest, textStatus);
		}
	};
	jQuery.ajax(params);
}

// Add a product and show modal cart
var add_item_show_modalCart = function(id) {
	if( check_show_modal ) {
		check_show_modal = false;
		timeOut_modalCart = setTimeout(function(){
			check_show_modal = true;
		}, 3000);
		if ( $('.addtocart-modal').hasClass('clicked_buy') ) {
			var quantity = $('#quantity').val();
		} else {
			var quantity = 1;
		}
		var params = {
			type: 'POST',
			url: '/cart/add.js',
			async: true,
			data: 'quantity=' + quantity + '&id=' + id,
			dataType: 'json',
			success: function(line_item) {
				if ( jQuery(window).width() >= 768 ) {
					getCartModal();
					jQuery('#myCart').modal('show');
					jQuery('.modal-backdrop').css({'height':jQuery(document).height(),'z-index':'99'});
				} else {
					window.location = '/cart';
				}
				$('.addtocart-modal').removeClass('clicked_buy');
			},
			error: function(XMLHttpRequest, textStatus) {
				Haravan.onError(XMLHttpRequest, textStatus);
			}
		};
		jQuery.ajax(params);
	}
}

// Plus number quantiy product detail
var plusQuantity = function() {
	if ( jQuery('input[name="quantity"]').val() != undefined ) {
		var currentVal = parseInt(jQuery('input[name="quantity"]').val());
		if (!isNaN(currentVal)) {
			jQuery('input[name="quantity"]').val(currentVal + 1);
		} else {
			jQuery('input[name="quantity"]').val(1);
		}
	}else {
		console.log('error: Not see elemnt ' + jQuery('input[name="quantity"]').val());
	}
}

// Minus number quantiy product detail
var minusQuantity = function() {
	if ( jQuery('input[name="quantity"]').val() != undefined ) {
		var currentVal = parseInt(jQuery('input[name="quantity"]').val());
		if (!isNaN(currentVal) && currentVal > 1) {
			jQuery('input[name="quantity"]').val(currentVal - 1);
		}
	}else {
		console.log('error: Not see elemnt ' + jQuery('input[name="quantity"]').val());
	}
}

// Change handleize
var slug = function(str) {
	str = str.toLowerCase();
	str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
	str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
	str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
	str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
	str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
	str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
	str = str.replace(/đ/g, "d");
	str = str.replace(/!|@|%|\^|\*|\(|\)|\+|\=|\<|\>|\?|\/|,|\.|\:|\;|\'| |\"|\&|\#|\[|\]|~|$|_/g, "-");
	str = str.replace(/-+-/g, "-"); //thay thế 2- thành 1-
	str = str.replace(/^\-+|\-+$/g, "");
	return str;
}

// Resize image article thumb for blog home
var imageThumbResize = function(){
	if ( jQuery('.lists-thumb-resize .box-thumb-resize').length > 1 ) {
		var height_thumb_resize = 0;
		jQuery.each(jQuery('.lists-thumb-resize .box-thumb-resize'),function(i,v){
			if ( jQuery(this).find('.image-thumb-resize').height() > height_thumb_resize ) {
				height_thumb_resize = jQuery(this).find('.image-thumb-resize').height();
			}
		});
		jQuery('.lists-thumb-resize .box-thumb-resize .image-thumb-resize').css('height',height_thumb_resize);
	}
}
// Resize image article thumb for blog home
var imageBlogResize = function(){
	if ( jQuery('.lists-blog-resize').length > 1 ) {
		var height_blog_resize = 0;
		jQuery.each(jQuery('.lists-blog-resize .image-blog-resize'),function(i,v){
			if ( jQuery(this).height() > height_blog_resize ) {
				height_blog_resize = jQuery(this).height();
			}
		});
		jQuery('.lists-blog-resize .image-blog-resize').css('height',height_blog_resize);
	}
}

jQuery(document).ready(function(){
	jQuery('.product-comments .tab-content > div').eq(0).addClass('active');


	// Scroll Top
	jQuery(document).on("click", ".back-to-top", function(){
		jQuery(this).removeClass('display');
		jQuery('html, body').animate({
			scrollTop: 0
		}, 600);
	});

	// Event click dropdown catalog mobile
	jQuery(document).on("click", ".event-drop-list", function(){
		if ( jQuery(this).hasClass('fa-angle-up') ){
			jQuery(this).removeClass('fa-angle-up').addClass('fa-angle-down');
			jQuery(this).parents('.box-section-collection').find('.catalog-list').css('margin-bottom','0px').slideUp('normal');
		}else {
			jQuery(this).removeClass('fa-angle-down').addClass('fa-angle-up');
			jQuery(this).parents('.box-section-collection').find('.catalog-list').css('margin-bottom','10px').slideDown('normal');
		}
	});

	// Add attribute data-spy=scroll in element <a> when you click, it'll scroll to href="#abc"
	jQuery(document).on("click", "a[data-spy=scroll]", function(e) {
		e.preventDefault();
		jQuery('body').animate({scrollTop: (jQuery(jQuery(this).attr('href')).offset().top - 70) + 'px'}, 500);
	});

	// Add a product when you change variant in cart ( product detail )
	jQuery(document).on("click", "#buy-now", function(e) {
		e.preventDefault() ;
		var params = {
			type: 'POST',
			url: '/cart/add.js',
			data: jQuery('#add-item-form').serialize(),
			dataType: 'json',
			success: function(line_item) {
				window.location = '/checkout';
			},
			error: function(XMLHttpRequest, textStatus) {
				Haravan.onError(XMLHttpRequest, textStatus);
			}
		};
		jQuery.ajax(params);
	});

	// Active image thumb and change image featured ( product detail )
	jQuery(document).on("click", ".product-thumb img", function() {
		jQuery('.product-thumb').removeClass('active');
		jQuery(this).parents('.product-thumb').addClass('active');
		jQuery(".product-image-feature").attr("src",jQuery(this).attr("data-image"));
		jQuery(".product-image-feature").attr("data-zoom-image",jQuery(this).attr("data-zoom-image"));
	});

	// Click change slide next image featured ( product detail )
	jQuery(document).on("click", ".slide-next", function() {
		if(jQuery(".product-thumb.active").prev().length > 0){
			jQuery(".product-thumb.active").prev().find('img').click();
		}
		else{
			jQuery(".product-thumb:last img").click();
		}
	});

	// Click change slide prev image featured ( product detail )
	jQuery(document).on("click", ".slide-prev", function() {
		if(jQuery(".product-thumb.active").next().length > 0){
			jQuery(".product-thumb.active").next().find('img').click();
		}
		else{
			jQuery(".product-thumb:first img").click();
		}
	});

	// Menu breadcrumb
	jQuery(document).on("hover", ".dropdown-link-breadcrumb li,.box-menu-collection li", function(e) {
		jQuery(this).toggleClass('open');
	});

	// Change group box search in header
	jQuery(document).on("click", ".change-collection-id > li > a", function() {
		jQuery(this).parents('.group-collection-search').children('button').html(jQuery(this).html() + "<i class='fa fa-search'></i>");
	});

	// Hover show group collection in header fix scroll
	jQuery(document).on("hover", ".fix-menu-collection .title-danh-muc", function(e) {
		if (e.type === "mouseenter") {
			jQuery('ul.dropdown-menu.box-menu-collection').slideDown(300);
		} else if (e.type === "mouseleave") {
			setTimeout(function(){
				if ( view_collection ) {
					jQuery('ul.dropdown-menu.box-menu-collection').slideUp(300);
				}
			},600);
		}
	});
	jQuery(document).on("hover", "ul.dropdown-menu.box-menu-collection", function(e) {
		if (e.type === "mouseenter") {
			view_collection = false;
		} else if (e.type === "mouseleave") {
			view_collection = true;
			jQuery('ul.dropdown-menu.box-menu-collection').slideUp(300);
		}
	});

	// Cart header hover
	jQuery('.cart-link').hover(function() {
		jQuery('.cart-view').slideDown(200);
	}, function() {
		setTimeout(function() {
			if (viewout) jQuery('.cart-view').slideUp(200);
		}, 500)
	})
	jQuery('.cart-view').hover(function() {
		viewout = false;
	}, function() {
		viewout = true;
		jQuery('.cart-view').slideUp(100);
	})

	// Click active icon index
	jQuery(document).on("click", "#category_icon_floor li", function() {
		jQuery('#category_icon_floor li.active');
		jQuery('#category_icon_floor li').removeClass('active');
		jQuery(this).addClass('active');
	});

	// Event slider
	var owl_data = jQuery("#slider-menu .owl-carousel").data('owlCarousel');
	jQuery('#slider-thumb li .slider-image-thumb').click(function(){
		jQuery(this).parent('li').addClass('click-event');
		jQuery('#slider-thumb li').removeClass('active');
		jQuery(this).parent('li').addClass('active');
		var index = jQuery(this).parent('li').index();
		owl_data.to(index);
	});

	jQuery('#slider-thumb li').hover(
		function(){
			var index = jQuery(this).index();
			var obj = jQuery(this);
			if( obj.hasClass('click-event') ){
			} else {
				obj.addClass('click-event');
				jQuery('#slider-thumb li').removeClass('active');
				obj.addClass('active');
				owl_data.to(index);
			}
		},
		function(){
			var index = jQuery(this).index();
			var obj = jQuery(this);
		}
	);

	// Fix height image for blog home
	imageThumbResize();
	imageBlogResize();

	// Ajax get product in collection for page home
	$('#ajax-tab-collection').tabdrop({text: '<i class="fa fa-bars"></i>'});
	$('#tab-product-template').tabdrop({text: '<i class="fa fa-bars"></i>'});

	jQuery(document).on("click", "#ajax-tab-collection > li:not(.tabdrop), #ajax-tab-collection li .dropdown-menu > li", function(e) {
		var handle = jQuery(this).children('a').attr('data-handle');
		jQuery('.tabs-products .tab-item.active').find('.icon-loading.tab-index').show();
		if ( jQuery('.tabs-products .tab-item.active').attr('data-get') == 'false' && handle != '' ) {
			jQuery.ajax({
				url: 'collections/' + handle + '?view=filter-tab-home&page=1',
				success:function(data){
					$('.icon-loading.tab-index').hide();
					jQuery('.tabs-products .tab-item.active').attr('data-get','true').children('.owl-carousel').append(data);
					jQuery('.tabs-products .tab-item.active').imagesLoaded(function() {
						var owl_tab = jQuery('.tabs-products .tab-item.active .owl-carousel');
						owl_tab.owlCarousel({
							items:5,
							nav:true,
							margin: 20,
							loop: true,
							responsive:{
								0:{
									items:2
								},
								768:{
									items:3
								},
								992:{
									items:5
								},
								1200:{
									items:5
								}
							}
						});
						owl_tab.find('.owl-next').css({"position":"absolute","right":"5px","top":"40%"}).html("<img src='//hstatic.net/203/1000094203/1000162309/btn-arrow-right.png?v=814' />");
						owl_tab.find('.owl-prev').css({"position":"absolute","left":"5px","top":"40%"}).html("<img src='//hstatic.net/203/1000094203/1000162309/btn-arrow-left.png?v=814' />");
						checkItemOwlShow($('.tabs-products'),'tab',5,5,3,2);
						jQuery(window).resize();
					});

				}
			});
		}
		jQuery('.tabs-products .tab-item.active').find('.icon-loading.tab-index').hide();
		if ( jQuery('.tabs-products .tab-item.active').attr('data-get') == 'false' && handle == '' ) {
			jQuery('.tabs-products .tab-item.active').attr('data-get','true').children('.owl-carousel').removeClass('product-lists').show().append("<div class='alertNoProduct'>Hiện tại cửa hàng mình đang cập nhật dữ liệu</div>");
		}
		jQuery(window).resize();
	});

	// Scroll show icon section index
	jQuery(window).on("scroll", function() {
		/* Xữ lý scroll change icon section */
		if ( jQuery('.box-section-collection').last().length > 0 ) {
			var pos_current = jQuery(window).scrollTop();
			if ( pos_current > jQuery('.box-section-collection').last().offset().top || pos_current < jQuery('header').height() + jQuery('#slider').height() ) {
				jQuery('#category_icon_floor').removeClass('affix').addClass('affix-top');
			}else {
				var icon_left = (jQuery(window).width() - jQuery('.container').width())/2 - 40;
				jQuery('#category_icon_floor').removeClass('affix-top').addClass('affix').css('left',icon_left);
			}
			if ( jQuery('#section_1').length > 0 ) {
				if( jQuery(window).scrollTop() > jQuery('#section_1').offset().top - 150 ) {
					jQuery('#scroll-left-price li.active').removeClass('active');
					jQuery('#scroll-left-price li').eq(0).addClass('active');
					jQuery('#category_icon_floor li').removeClass('active');
					jQuery('#category_icon_floor li').eq(0).addClass('active');
				}
			}
			if ( jQuery('#section_2').length > 0 ) {
				if( jQuery(window).scrollTop() > jQuery('#section_2').offset().top - 280 ) {
					jQuery('#scroll-left-price li.active').removeClass('active');
					jQuery('#scroll-left-price li').eq(1).addClass('active');
					jQuery('#category_icon_floor li').removeClass('active');
					jQuery('#category_icon_floor li').eq(1).addClass('active');
				}
			}
			if ( jQuery('#section_3').length > 0 ) {
				if( jQuery(window).scrollTop() > jQuery('#section_3').offset().top - 280 ) {
					jQuery('#scroll-left-price li.active').removeClass('active');
					jQuery('#scroll-left-price li').eq(2).addClass('active');
					jQuery('#category_icon_floor li').removeClass('active');
					jQuery('#category_icon_floor li').eq(2).addClass('active');
				}
			}
			if ( jQuery('#section_4').length > 0 ) {
				if( jQuery(window).scrollTop() > jQuery('#section_4').offset().top - 280 ) {
					jQuery('#scroll-left-price li.active').removeClass('active');
					jQuery('#scroll-left-price li').eq(3).addClass('active');
					jQuery('#category_icon_floor li').removeClass('active');
					jQuery('#category_icon_floor li').eq(3).addClass('active');
				}
			}
		}
		/* Process scroll header top  */
		if ( jQuery(window).width() >= 768 ) {
			if( jQuery(window).scrollTop() > 0 ) {
				jQuery('.top-header').addClass('fix-header');
				jQuery('nav').addClass('fix-header-nav');
			} else {
				jQuery('.top-header').removeClass("fix-header");
				jQuery('nav').removeClass('fix-header-nav');
			}
		} else {
			if( jQuery(window).scrollTop() > 0 ) {
				jQuery('nav.navbar-main.navbar').addClass('affix-mobile');
			} else {
				jQuery('nav.navbar-main.navbar').removeClass("affix-mobile");
			}
			if ( cur_scrollTop > jQuery(window).scrollTop() ) {
				jQuery('nav.navbar-main.navbar').removeClass('affix-mobile');
			}
		}
		cur_scrollTop = jQuery(window).scrollTop();
		/* Process scroll btn addtocart in product */
		if ( jQuery('.btn-position').length > 0 ) {
			if( jQuery(window).scrollTop() > jQuery('.addtocart-modal').offset().top ) {
				jQuery('.btn-position').show('slow');
			}else {
				jQuery('.btn-position').hide('slow');
			}
		}
		/* Scroll to top */
		if ( jQuery('.back-to-top').length > 0 && jQuery(window).scrollTop() > 500 ) {
			jQuery('.back-to-top').addClass('display');
		} else {
			jQuery('.back-to-top').removeClass('display');
		}
	});

});

// Cart header hover
jQuery(document).ready(function(){
	jQuery('.catalogue-link').hover(function() {
		jQuery('.catalogue-view').slideDown(200);
	}, function() {
		setTimeout(function() {
			if (viewout) jQuery('.catalogue-view').slideUp(200);
		}, 500)
	})
	jQuery('.catalogue-view').hover(function() {
		viewout = false;
	}, function() {
		viewout = true;
		jQuery('.catalogue-view').slideUp(100);
	})

})

// Event toggle menu mobile
jQuery(document).ready(function(){
	if ( jQuery(window).width() < 768  ) {
		jQuery('.footer-center-wrap').on("click",".toggle-mb .toggle-mb-title",function(){
			if ( $(this).attr('aria-expanded') == 'true' ) {
				$(this).attr('aria-expanded','false');
				$(this).parent().children('.toggle-mb-content').slideUp();
			} else {
				$(this).attr('aria-expanded','true');
				$(this).parent().children('.toggle-mb-content').slideDown();
			}
		});
	}
	/*
$('.owl-slide-cate').owlCarousel({
	items:1,
	loop: true,
	smartSpeed: 800,
	autoplay:true,
	autoplayTimeout:5000,
	autoplayHoverPause:true,

});
*/
	if($(".owl-slide-cate-1").length > 0){
		$('.owl-slide-cate-1').owlCarousel({
			items:1,
			loop:$(".owl-carousel > .col-item").length <= 1 ? false : true,
			smartSpeed: 800,
			autoplay:true,
			autoplayTimeout:5000,
			autoplayHoverPause:true,
			dots:true,

		});
	}
	if($(".owl-slide-cate-2").length > 0){
		$('.owl-slide-cate-2').owlCarousel({
			items:1,
			loop:$(".owl-carousel > .col-item").length <= 1 ? false : true,
			smartSpeed: 800,
			autoplay:true,
			autoplayTimeout:5000,
			autoplayHoverPause:true,
			dots:true,

		});
	}
	if($(".owl-slide-cate-3").length > 0){
		$('.owl-slide-cate-3').owlCarousel({
			items:1,
			loop:$(".owl-carousel > .col-item").length <= 1 ? false : true,
			smartSpeed: 800,
			autoplay:true,
			autoplayTimeout:5000,
			autoplayHoverPause:true,
			dots:true,

		});
	}
	if($(".owl-slide-cate-4").length > 0){
		$('.owl-slide-cate-4').owlCarousel({
			items:1,
			loop:$(".owl-carousel > .col-item").length <= 1 ? false : true,
			smartSpeed: 800,
			autoplay:true,
			autoplayTimeout:5000,
			autoplayHoverPause:true,
			dots:true,

		});
	}
	if($(".owl-slide-cate-5").length > 0){
		$('.owl-slide-cate-5').owlCarousel({
			items:1,
			loop:$(".owl-carousel > .col-item").length <= 1 ? false : true,
			smartSpeed: 800,
			autoplay:true,
			autoplayTimeout:5000,
			autoplayHoverPause:true,
			dots:true,

		});
	}
})

jQuery(window).ready(function() {
	jQuery('.faqs .panel-title a').addClass('collapsed');
	jQuery('.faqs .panel-group .panel .panel-collapse.in').prev().find('.panel-title a').removeClass('collapsed');

});
