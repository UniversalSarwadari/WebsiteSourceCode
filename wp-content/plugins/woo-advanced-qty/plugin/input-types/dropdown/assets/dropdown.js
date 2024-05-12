jQuery(document).ready(function( $ ) {
	$(document).on('found_variation.wc-variation-form', '.variations_form', function(event, variation) {
		var form = $(event.target),
			qty_field = form.find('.single_variation_wrap .quantity select.qty');

		let valid_value_keys = Object.keys(variation.valid_values).sort((x,y)=>Number(x)-Number(y));

		qty_field.empty();

		$.each(valid_value_keys, function(key, value) {
			qty_field.append($('<option></option>').attr('value', value).text(variation.valid_values[value]))
		});

		qty_field.val(variation.input_value);
	});
});