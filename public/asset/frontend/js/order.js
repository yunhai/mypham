$(function() {
    $('#province').change(function() {
        var province = $(this).val();
        var district = $('#district').data('value');

        var items = locations[province];
        var html = '<option value="0">Vui lòng chọn quận huyện.</option>';
        if (parseInt(province) > 0) {
            for (var i in items) {
                selected = '';
                if (items[i]['id'] == district) {
                    selected = 'selected ';
                }
                html += "<option value='" + items[i]['id'] + "' " +  selected + ">" + items[i]['title']+ "</option>"
            }
        }

        $('#district').html(html);

        $('#deliver_day').html('');
        $('#deliver_price').html('');

    })

    $('#district').change(function() {
        var province = $('#province').val();
        var district = $(this).val();

        $('#deliver_price').html('');
        $('#deliver_day').html('');

        if (province == 0) {
            return true;
        }

        var item = locations[province][district];

        var price = parseInt(item.delivery_price);
        price = price.toLocaleString();
        $('#deliver_price').html("Phí vận chuyển: <b>" + price + "</b> VND<br />");

        var day = item.delivery_day;
        $('#deliver_day').html("Số ngày vận chuyển: <b>" + day + "</b> ngày");
    })

    $('#province').trigger('change');
    $('#district').trigger('change');

    $('#buyer_seperate').change(function() {
        if ($(this).is(':checked')) {
            $('.j-buyer_seperate').removeClass('hidden');
        } else {
            $('.j-buyer_seperate').addClass('hidden');
        }
    });
})
