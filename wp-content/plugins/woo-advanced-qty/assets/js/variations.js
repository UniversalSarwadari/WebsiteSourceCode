jQuery(document).ready(function ($) {
	$(document).on('found_variation.wc-variation-form', '.variations_form', function(event, variation) {
		var form = $(event.target),
			qty_field = form.find('.single_variation_wrap .quantity input.qty');

		qty_field.attr('step', variation.step);
		qty_field.attr('value', variation.input_value);
		qty_field.trigger('changed_variation', [variation])
	});
});