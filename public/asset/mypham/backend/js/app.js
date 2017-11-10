$(document).on('click', '.nav-offcanvas', function(e) {
  var $this = $(e.target);
  var target = $this.data('target');

  $this.toggleClass('fa-indent');
  $(target).toggleClass('collapse nav-off-screen');
});

$(document).on('click', '.sidebar-offcanvas', function(e) {
    var target = $(this).data('target');

    var width = $(target).width();
    $i = $(this).find('i');
    if ($i.hasClass('fa-angle-double-left')) {
        $i.removeClass('fa-angle-double-left').addClass('fa-angle-double-right');
        $(target).animate({"right": "+=" + width + "px" }, 700).promise();
        $(this).animate({"right": "+=" + width + "px" }, 1500).promise();
    } else {
        $(target).animate({"right": "-=" + width + "px" }, 500).promise();
        $(this).animate({"right": "-=" + width + "px" }, 1500).promise().done(function(){
            $i.removeClass('fa-angle-double-right').addClass('fa-angle-double-left');
        });
    }
});
