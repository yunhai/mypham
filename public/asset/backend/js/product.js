$(document).on('click', '.j-addProperty', function(event) {
    var list = $('#property-list').find('.init--property');

    if (list.length > 0) {
        var enable = list[0];
        $(enable).removeClass('init--property');
    }
});

$(document).on('click', '.j-removeProperty', function(event) {
    var target = $(event.target);
    var id = target.data('id');
    $('#' + id).remove();
});

$(document).on('click', '.j-addPropertyDetail', function(event) {
    var target = $(event.target);
    var targetId = target.data('target');
    var list = $('#' + targetId).find('.init--property-detail');

    if (list.length > 0) {
        var enable = list[0];
        $(enable).removeClass('init--property-detail');
    }
});

$(document).on('click', '.j-removePropertyDetail', function(event) {
    var target = $(event.target);
    var id = target.data('id');
    $('#' + id).remove();
});

$(document).on('click', '.j-removePropertyDetailFile', function(event) {
    var target = $(event.target);
    var id = target.data('id');
    $('#img-container-' + id).remove();
    var button = target.data('button');

    $('#' + button ).removeClass('b-upload-hide');
});






















$(".datepicker-input").each(function(){ $(this).datepicker();});
$('#product-option').tagEditor({
    delimiter: ',',
    placeholder: 'Enter tags ...',
});







//http://selectize.github.io/selectize.js/
$('#select-repo').selectize({
    valueField: 'id',
    labelField: 'title',
    searchField: 'title',
    create: false,

    render: {
        option: function(item, escape) {
            return '<div>' +
                '<span class="title">' +
                    '<span class="name">' + escape(item.title) + '</span>' +
                '</span>' +
                '<span class="description">' + escape(item.address) + '</span>' +
                '<ul class="meta">' +
                    '<li><span>' + escape(item.phone) + '</span></li>' +
                    '<li><span>' + escape(item.email) + '</span></li>' +
                '</ul>' +
            '</div>';
        }
    },
    score: function(search) {
        var score = this.getScoreFunction(search);
        return function(item) {
            return score(item);
        };
    },

    load: function(query, callback) {
        if (!query.length) return callback();

        var url = $('#select-repo').data('url');
        url += '?q=' + encodeURIComponent(query);

        $.ajax({
            url: url,
            type: 'GET',
            error: function() {
                callback();
            },
            success: function(res) {
               callback($.map(res, function(el) { return el }));
            }
        });
    }
});


$('#select-manufacturer').selectize({
    valueField: 'id',
    labelField: 'title_origin',
    searchField: 'title',
    create: false,

    render: {
        option: function(item, escape) {
            return '<div>' +
                '<span class="title">' +
                    '<span class="name">' +
                        escape(item.title) +
                    '</span>' +
                '</span>' +
                '<ul class="meta">' +
                    '<li><span>' + escape(item.origin) + '</span></li>' +
                '</ul>' +
            '</div>';
        }
    },
    score: function(search) {
        var score = this.getScoreFunction(search);
        return function(item) {
            return score(item);
        };
    },

    load: function(query, callback) {
        if (!query.length) return callback();

        var url = $('#select-manufacturer').data('url');
        url += '?q=' + encodeURIComponent(query);

        $.ajax({
            url: url,
            type: 'GET',
            error: function() {
                callback();
            },
            success: function(res) {
               callback($.map(res, function(el) { return el }));
            }
        });
    }
});
