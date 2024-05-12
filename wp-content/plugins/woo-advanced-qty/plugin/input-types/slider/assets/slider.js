jQuery(document).ready(function( $ ) {

	$(document).on('updated_wc_div', function () {
		initializeSliders();
	});

	function initializeSliders() {
		$(".slider-input .quantity .slider").each(function() {
			let handle = $(this).find('.custom-handle');
			let input = $(this).siblings('input.qty');
			let valid_values = input.data('valid_values');
			let valid_value_keys = Object.keys(valid_values).sort((x,y)=>Number(x)-Number(y));
			let standard_value_index = valid_value_keys.findIndex(function (element) {
				return element == input.val();
			});
			$(this).slider({
				min: 0,
				max: valid_value_keys.length - 1,
				step: 1,
				value: standard_value_index,
				create: function() {
					handle.text(valid_values[valid_value_keys[$(this).slider("value")]]);
				},
				slide: function(event, ui) {
					let valid_values = input.data('valid_values');
					let valid_value_keys = Object.keys(valid_values).sort((x,y)=>Number(x)-Number(y));

					handle.text(valid_values[valid_value_keys[ui.value]]);

					input.val(valid_value_keys[ui.value]).change();
				}
			});
		});
	}

	initializeSliders();

	$(document).on('changed_variation', '.single_variation_wrap .quantity input.qty', function(event, variation) {
		let qty_field = $(this),
			slider_object = qty_field.siblings('.slider'),
			handle = slider_object.find('.custom-handle');

		qty_field.data('valid_values', variation.valid_values);

		let valid_values = qty_field.data('valid_values');
		let valid_value_keys = Object.keys(valid_values).sort((x,y)=>Number(x)-Number(y));

		let standard_value_index = valid_value_keys.findIndex(function (element) {
			return element == qty_field.val();
		});

		slider_object.slider('option', 'max', valid_value_keys.length - 1);
		slider_object.slider('option', 'value', standard_value_index);
		handle.text(valid_values[valid_value_keys[standard_value_index]]);
	});
});