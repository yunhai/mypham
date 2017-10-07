function initialize() {
	var latlng, address;

	var geocoder = new google.maps.Geocoder();

	if (geocoder) {
		var option = {
			zoom: 18,
			draggable:true,
			animation: google.maps.Animation.DROP,
			mapTypeControl: true,
			mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
			navigationControl: true,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};

		map = new google.maps.Map(document.getElementById("map_canvas"), option);

		var target = $('#map_canvas');
		var address = $('#' + target.data('address')).val();
		var geoOption = {'address': address};

		if (!$('#' + target.data('address')).hasClass('dirty')) {
			var latlngStr = $('#' + target.data('geo')).val();
			if (latlngStr) {
				latlngStr = latlngStr.split(',', 2);
				latlng = {lat: parseFloat(latlngStr[0]), lng: parseFloat(latlngStr[1])};
				geoOption = {'location': latlng};
			}
		}

		geocoder.geocode(geoOption, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
					latlng = results[0].geometry.location
					map.setCenter(latlng);

					marker = new google.maps.Marker({
						position: results[0].geometry.location,
						map: map,
						title: address,
						draggable: true,
						animation: google.maps.Animation.DROP,
					});

					content = $('#' + target.data('template')).html();
					content = content.replace('%s', address);

					option = {
						content: content,
						size: new google.maps.Size(150,50)
					}
					var infowindow = new google.maps.InfoWindow(option).open(map, marker);

					var str = results[0].geometry.location.lat() + ',' + results[0].geometry.location.lng()
					$('#' + target.data('geo')).val(str);

					google.maps.event.addListener(marker, 'mouseup', function(event) {
			    		var str = event.latLng.lat() + ',' + event.latLng.lng();
						$('#' + target.data('geo')).val(str);
		  			});
				}
			}
		});
	}
}

$('#store-address').change(function() {
	$(this).addClass('dirty');
	initialize();
})

initialize();