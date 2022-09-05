function Widget(){

	this.map_zoom = 12;

	this.pos_defined = function(){
		return (this.pos_lat !== null) && (this.pos_lon !== null);
	}

	this.map_create = function(){
		return new Promise((resolve, reject) => {
			if(this.pos_defined() === true){
				let map_options = {
					zoomControl:false,
				};
				this.map = L.map('map', map_options).setView([this.pos_lat, this.pos_lon], this.map_zoom);
				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {}).addTo(this.map);
				new L.Control.Zoom({position: 'topright'}).addTo(this.map);



				resolve();
			}else{
				reject('Не определена позиция');
			}
		})
	}

	this.get_pvz = function(){
		return new Promise((resolve, reject) => {
			var _this = this;
			$.ajax({
				url: '/include/cdek_map_widget/ajax.php',
				method: 'POST',
				data: {
					action: 'get_pvz_by_postal_code',
					post_code: _this.post
				},
				success: function(data){
					resolve(data);
				}
			})
		})
	}

	this.get_pvz_by_id = function(id){
		return new Promise((resolve, reject) => {
			var _this = this;
			$.ajax({
				url: '/include/cdek_map_widget/ajax.php',
				method: 'POST',
				data: {
					action: 'get_pvz_by_id',
					id: id
				},
				success: function(data){
					resolve(data);
				}
			})
		})
	}

	this.set_marker = function(code, lat, lon, address, phone, address_detail, shedule){

		function build_popup(){

			return 	"<div data-code='" + code + "'><strong>" + address + "</strong></div>" +
					"<div><i class=\"fa fa-phone\"></i>" + phone + "</div>" +
					"<div><i class=\"fa fa-clock-o\"></i>" + shedule + "</div>" +
					"<div>" + address_detail + "</div>" +
					"<div class=\"check_button\"><i class=\"fa fa-check\"></i><span>Выбрать</span></div>";
		}

		if(this.map !== null){

			var popup_options = {
				className: 'cdek_pvz_popup',
				closeButton: false,
				autoPanPaddingTopLeft: L.point(10, 55)
			};


			let marker = L.marker([lat, lon]).bindPopup(build_popup(), popup_options).addTo(this.map);
			this.markers.push(marker);
		}else{
			console.error('Не создана карта');
		}
	}

	this.get_position_auto = function(){
		return new Promise((resolve, reject) => {
			var _this = this;
			$.ajax({
				url: '/include/cdek_map_widget/ajax.php',
				method: 'POST',
				data: {
					action: 'get_position_info',
				},
				success: function(data){

					_this.cur_city = data.city.name_ru;
					_this.post = data.city.post;
					_this.pos_lat = data.city.lat;
					_this.pos_lon = data.city.lon;

					resolve();
				}
			})
		})
	}

	this.get_city_suggestions = function(city){
		return new Promise((resolve, reject) => {
			$.ajax({
				url: '/include/cdek_map_widget/ajax.php',
				method: 'POST',
				data: {
					q: city,
					action: 'get_city_suggestions',
				},
				success: function(data){
					resolve(data);
				}
			});
		})
	}

	this.get_city_by_id = function(id){
		return new Promise((resolve, reject) => {
			$.ajax({
				url: '/include/cdek_map_widget/ajax.php',
				method: 'POST',
				data: {
					action: 'get_city_by_id',
					id: id
				},
				success: function(data){
					resolve(data);
				}
			})
		})
	}


	this.init = async function(){
		var _this = this;
		_this.markers = [];
		await _this.get_position_auto();
		await _this.map_create();

		$.each(await _this.get_pvz(), function(index, item){
			_this.set_marker(item.code, item.lat, item.lon, item.address, item.phone, item.address_detail, item.shedule);
		})
	}
}





$(document).ready(function(){

	var widget = new Widget();

	$(document).on('click', '.leaflet-popup-content .check_button', function(){
		widget.map.closePopup();

		let text = $(this).parent().find('div').eq(0).text();
		let code = $(this).parent().find('div').data('code');

		$('input#property_ADDRESS').val(code + '#' + text);
		$.fancybox.close()

	})

	var search_container = $('.pvz_change_window .city_name .search_result')
	var search_input = $('.pvz_change_window .city_name input');

	$('.pvz_change_window .city_name').hover(function(){
		if(search_container.find('.city_item').not('.blank').length > 0){
			search_container.show();
			widget.map.closePopup();
		}
	}, function(){
		search_container.hide();
	})



	search_input.on('keyup', function(e){
		let input = this;
		clearTimeout($(input).data('timer'));
		$(input).data('timer', setTimeout(async function(){
			$(input).removeData('timer');

			if($(input).val() !== ''){
				let data = await widget.get_city_suggestions($(input).val());

				if(Object.keys(data).length > 0){

					search_container.find('.city_item').not('.blank').remove();

					$.each(data.geonames, function(i, city){

						let cloned = search_container.find('.city_item.blank').clone();
						cloned.removeClass('blank').attr('data-id', city.id).find('.city').text(city.cityName).next().text(city.regionName);
						cloned.appendTo(search_container);
						search_container.show();
						widget.map.closePopup();
					})
				}

			}else{
				search_container.find('.city_item').not('.blank').remove();
				search_container.hide();
			}

		}, 300));

	});

	$(document).on('click', '.city_item', async function(){
		search_container.hide();
		var city_item = this;
		search_input.val($(city_item).find('.city').text());

		var data = await widget.get_city_by_id($(city_item).data('id'))
		if(data.length > 0){

			widget.map.panTo(L.latLng(data[0].lat, data[0].lon));

			$.each(widget.markers, function(i, marker){
				widget.map.removeLayer(widget.markers[i]);
			})

			widget.markers = [];
			$.each(await widget.get_pvz_by_id($(city_item).data('id')), function(index, item){
				widget.set_marker(item.code, item.lat, item.lon, item.address, item.phone, item.address_detail, item.shedule);
			})
		}
	})

	$(document).on('click', '.show_map', function(){

		var pvz_window = $('.pvz_change_window');
		$.fancybox.open(pvz_window, {
			beforeLoad: function(){
				if(typeof widget.map === 'undefined'){
					widget.init();
				}
			},
			afterClose: function(){
				widget.map.closePopup();
				search_container.hide().find('.city_item').not('.blank').remove();
				search_input.val('')
			},
			width:pvz_window.width(),
			height:pvz_window.height(),
			autoDimensions: false,
			autoSize:false
		});
	});


});