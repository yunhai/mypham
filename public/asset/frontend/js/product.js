$('.j-property').click(function() {
    var self = $(this);
    var id = self.data('id');
    $('.j-property').removeClass('active');
    $('.j-propertyInput').attr("disabled", "disabled");
    $('#j-propertyInput'+id).removeAttr("disabled");
    self.addClass('active');

    $('.j-propertyDetailContainer').hide();
    $('#property-detail-' + id).show();
})

$('.j-propertyDetail').click(function() {
    var self = $(this);
    var id = self.data('id');
    $('.j-propertyDetail').removeClass('active');
    $('.j-propertyDetailInput').attr("disabled", "disabled");
    $('#j-propertyDetailInput'+id).removeAttr("disabled");

    self.addClass('active');
})

$(document).ready(function() {
    get($('#vote-list'));
    get($('#faq-list'));
});

function get(target) {
    $.ajax({
        type: "get",
        url: target.data('url'),
        dataType : "html",
        ContentType : 'application/x-www-form-urlencoded; charset=utf-8',
        success: function(data)
        {
            target.append(data); // show response from the php script.
        }
    });
}

$("#product-vote-form").submit(function(e) {
    $.ajax({
        type: "post",
        url: $(this).data('action'),
        data: $(this).serialize(),
        success: function(data)
        {
            $('#product-vote-form').get(0).reset();
            $('#vote-callback').show();
            setTimeout(function() {
                $('#vote-callback').hide();
            }, 10000);
        }
    });

    e.preventDefault(); // avoid to execute the actual submit of the form.
    return false;
});

$("#product-faq-form").submit(function(e) {
    $.ajax({
        type: "post",
        url: $(this).data('action'),
        data: $(this).serialize(), // serializes the form's elements.
        success: function(data)
        {
            $('#faq-callback').show();
            setTimeout(function() {
                $('#faq-callback').hide();
            }, 10000);
            $("#product-faq-form").get(0).reset();
        }
    });

    e.preventDefault(); // avoid to execute the actual submit of the form.
    return false;
});
