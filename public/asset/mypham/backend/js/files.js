// // Sortable.create($(".files-container")[0]);
//
// $('#property-uploader').fileapi({
//     multiple: true,
//     autoUpload: true,
//     duplicate: true,
//     maxFiles: 1,
//
//     onFileComplete: function (evt, uiEvt) {
//         var result = uiEvt.result; // server response
//
//         var target = uiEvt.file.$el;
//         console.log(uiEvt.file);
//         target.hide("slow");
//
//         var wrapper = target.data('wrapper');
//         var preview = target.data('preview');
//
//         var parent = $('#' + wrapper);
//         var html = "<div id='img-container-" + result.id + "' class='" + preview + "'>" +
//             "<input type='hidden' value='" + result.id + "' name='"  + result.container + "[" +  result.id + "][id]' />" +
//             "<figure class='nailthumb-container'>" +
//                 "<img src='"  + result.target + "' class='img-responsive'>" +
//                 "<figcaption>" +
//                     "<a href='javascript:;' class='remove-file' data-id='" + result.id + "'>Delete</a>" +
//                 "</figcaption>" +
//             "</figure>" +
//         "</div>";
//
//         parent.append(html).show();
//     },
//     elements: {
//         list: '.js-files',
//         file: {
//             tpl: '.js-file-tpl-property',
//             preview: {
//                 el: '.b-thumb__preview',
//                 width: 250,
//                 height: 150
//             },
//             upload: { show: '.progress' },
//             complete: { show: '.progress' },
//             progress: '.progress .bar'
//         },
//         dnd: {
//             el: '.b-upload__dnd',
//             hover: 'b-upload__dnd_hover',
//             fallback: '.b-upload__dnd-not-supported'
//         }
//     }
// });
