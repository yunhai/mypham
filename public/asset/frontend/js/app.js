$(document).ready(function(){
    if($(".banner-adv .owl-carousel").length > 0){
        $(".banner-adv .owl-carousel").owlCarousel({
            loop:$(".banner-adv .owl-carousel > .item").length <= 1 ? false : true,
            autoplay: true,
            items:1,
            nav: false,
            autoplayHoverPause: false,
            animateOut: 'slideOutUp',
            animateIn: 'slideInUp',
            autoplayTimeout:3000,
        });
    }
    if($(".banner-adv .owl-carousel-1").length > 0){
        $(".banner-adv .owl-carousel-1").owlCarousel({
            loop:$(".banner-adv .owl-carousel-1 > .item").length <= 1 ? false : true,
            autoplay: true,
            items:1,
            nav: false,
            autoplayHoverPause: false,
             animateOut: 'slideOutUp',
            animateIn: 'slideInUp',
            autoplayTimeout:3000,
        });
    }

    $(".j-loadmore").click(function() {
        var target = $(this).data('target');
        var url = $(this).data('url');
        var page = $(this).data('page') || 0;

        page = parseInt(page) + 1;
        url = url + '&page=' + page;
        $.ajax({
            type: "post",
            url: url,
            data: {'ajax': 1},
            success: function(data)
            {
                $(target).append(data.html);

                var btn = $('.j-loadmore');
                btn.data('page', data.current);
                if (data.current == data.total) {
                    btn.unbind('click').hide();
                }
            }
        });
    });
});
$('.easy-sidebar-toggle').click(function(e) {
    e.preventDefault();
    $('body').toggleClass('toggled');
    $('.navbar.easy-sidebar').removeClass('toggled');
});
