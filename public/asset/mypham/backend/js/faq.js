
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
                ['insert', ['link','hr']],
                ['view', ['fullscreen', 'codeview']],
                ['help', ['help']]
            ]
        }
    );
})
