var backend = {
    bindBulkAction: function() {
        $('#bulk-action-button').click(function() {
            var form = $('#bulk-action-list').data('form');
            var url  = $('#bulk-action-list').val();

            $('#'+form).attr("action", url).submit();
        })
    },
}

$(function() {
    backend.bindBulkAction();
})