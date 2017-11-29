function sendFile(file, target) {
    data = new FormData();

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
})
