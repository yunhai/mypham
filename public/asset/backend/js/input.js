function sendFile(file, target) {
    data = new FormData();
    data.append("file", file);
    $.ajax({
        data: data,
        type: "POST",
        url: target.data('upload'),
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(callback) {
            if (callback.success) {
                var html = "<img style='max-width:100%;' src='" + callback.target + "' alt='" + callback.caption
 + "' data-id='" + callback.id + "' class='em-" + callback.container + "' />";
                var node = $(html);
                $summernote.summernote('insertNode', node[0]);
            }
        }
    });
}

$(function() {
    $summernote = $('.summernote-editor').summernote(
        {
            height: 300,
            minHeight: null,
            maxHeight: null,
            toolbar: [
                ['headline', ['style']],
                ['style', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontclr', ['color']],
                ['alignment', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link','picture','video','hr']],
                ['view', ['fullscreen', 'codeview']],
                ['help', ['help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    sendFile(files[0], $(this));
                }
          }
        }
    );

    $('.nailthumb-container').nailthumb({method:'crop'});
})

$(document).on('click', '.remove-file', function() {
    var target = $(this).data('id');
    $('#img-container-' + target).remove();
})

if ($('#image-uploader').length) {
    $('#image-uploader').fileapi({
    multiple: true,
    autoUpload: true,
    duplicate: true,
    maxFiles: 1,
    onFileComplete: function (evt, uiEvt) {
        var result = uiEvt.result;
        var target = uiEvt.file.$el;
        var wrapper = target.data('wrapper');
        var preview = target.data('preview');

        var parent = $('#' + wrapper);
        var html = "<li id='img-container-" + result.id + "' class='" + preview + "'>" +
            "<input type='hidden' value='" + result.id + "' name='"  + result.container + "[file_id]' />" +
            "<figure class='nailthumb-container'>" +
                "<img src='"  + result.target + "' class='img-responsive'>" +
                "<figcaption>" +
                    "<a href='javascript:;' class='remove-file' data-id='" + result.id + "'>Delete</a>" +
                "</figcaption>" +
            "</figure>" +
        "</li>";

        target.hide("slow");
        parent.html(html).show();
    },
    elements: {
        list: '.js-files',
        file: {
            tpl: '.js-file-tpl-image',
            preview: {
                el: '.b-thumb__preview',
                width: 250,
                height: 150
            },
            upload: { show: '.progress' },
            complete: { show: '.progress' },
            progress: '.progress .bar'
        },
        dnd: {
            el: '.b-upload__dnd',
            hover: 'b-upload__dnd_hover',
            fallback: '.b-upload__dnd-not-supported'
        }
    }
});
}
