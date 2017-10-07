$(document).on('click', '.seo-autofill', function() {
    $(this).addClass('dirty');
})

$(".seo-permalink").change(function() {
    var text = '';

    var model = $(this).data('model');
    var target = $('#' + model + '-title');

    var title = target.val().replace(/^[ ]+|[ ]+$/g, "");
    var prefix = target.data('prefix');

    if (title) {
        target = $('#seo-title');
        if (!target.val() || !target.hasClass('dirty')) {
            target.val(title);
        }

        target = $('#seo-keyword');
        if (!target.val() || !target.hasClass('dirty')) {
            target.val(title.replace(/ /g, ', '));
        }

        target = $('#seo-desc');
        if (!target.html() || !target.hasClass('dirty')) {
            target.html(title.replace(/ /g, ', '));
        }

        title = common.removeAccent(title);
        category = common.removeAccent($("#" + model + "-category option:selected").text());
        if (category) {
            text = category + '/' + title;
        } else {
            text = prefix + '/' + title;
        }
    }


    if (text) {
        var field = ['seo-permalink', 'seo-canonical'];
        for (var i in field) {
            target = $('#' + field[i]);
            if (!target.val() || !target.hasClass('dirty')) {
                target.val(text.replace(/^\/|\/$/g, ""));
            }
        }
    }
});