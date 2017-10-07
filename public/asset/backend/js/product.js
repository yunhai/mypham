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