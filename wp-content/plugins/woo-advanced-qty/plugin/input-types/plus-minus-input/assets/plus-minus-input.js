jQuery(document).ready(function( $ ) {
	let timeout;
	let interval;
	let handling;

	$(document).on('touchstart mousedown', '.plus-minus-button .woo-advanced-minus', function (e) {
		if(e.type === 'touchstart') {
			handling = true;
		} else if(e.type === 'mousedown' && handling === true) {
			handling = false;
			return;
		}

		e.stopPropagation();

		let quantity_input = $(this).siblings('input.plus-minus-input');

		changeQuantity(minusQuantity, quantity_input);
	});

	$(document).on('touchstart mousedown', '.plus-minus-button .woo-advanced-plus', function (e) {
		if(e.type === 'touchstart') {
			handling = true;
		} else if(e.type === 'mousedown' && handling === true) {
			handling = false;
			return;
		}

		e.stopPropagation();

		let quantity_input = $(this).siblings('input.plus-minus-input');

		changeQuantity(plusQuantity, quantity_input);
	});

	$(document).on('touchend mouseup', function(e) {
		clearTimeout(timeout);
		clearInterval(interval);
	});

	$(document).on('found_variation.wc-variation-form', '.variations_form', function(event, variation) {
		var form = $(event.target),
			qty_field = form.find('.single_variation_wrap .quantity input.qty');

		qty_field.data('step_intervals', variation.step_intervals);
	});

	$(document).on('changed_variation', '.single_variation_wrap .quantity input.qty', function(event, variation) {
		let qty_field = $(this);
		if(typeof variation.step_intervals === "undefined" || variation.step_intervals === null) {
			qty_field.data('step_intervals', null);
		} else {
			qty_field.data('step_intervals', variation.step_intervals);
		}

	});

	function getValidQTY(min, step, value_max) {
		let value = min;
		while(value <= value_max) {
			value = parseQTY(value + step);
		}

		return parseQTY(value - step);
	}

	function getNewValue(input, plus) {
		let step_intervals = input.data('step_intervals');
		let current_value = parseQTY(input.val());
		let step = parseQTY(input.attr('step'));
		let new_value = null;
		let _new_value = null;

		if(typeof step_intervals !== "undefined" && step_intervals !== null) {
			step_intervals = step_intervals.slice();

			if(plus !== true) {
				step_intervals.reverse();
			}

			$.each(step_intervals, function(key, interval) {
				let interval_start = parseQTY(interval[0]);
				let interval_end = parseQTY(interval[1]);

				if(interval_start <= current_value && interval_end >= current_value) {
					let _step = parseQTY(interval[2]);

					if(plus === true) {
						_new_value = getValidQTY(interval_start, _step, parseQTY(current_value + _step));
						if(_new_value <= interval_end) {
							new_value = _new_value;
						}
					} else {
						if(current_value >= interval_end) {
							_new_value = getValidQTY(interval_start, _step, parseQTY(interval_end - 0.01));
						} else {
							_new_value = getValidQTY(interval_start, _step, parseQTY(current_value - _step));
						}

						if(_new_value >= interval_start) {
							new_value = _new_value;
						}
					}
				} else if(plus === true && _new_value >= interval_start) {
					new_value = parseQTY(interval_start);
				}
			});
		}

		if(new_value === null) {
			let min = parseQTY(input.attr('min'));
			if(plus === true) {
				new_value = getValidQTY(min, step, parseQTY(current_value + step));
			} else {
				new_value = getValidQTY(min, step, parseQTY(current_value - step));
			}
		}

		return new_value;
	}

	function changeQuantity(func, input) {
		func(input);

		timeout = setTimeout(function () {
			interval = setInterval(function() {
				func(input)
			}, 100);
		}, 500);
	}

	function minusQuantity(input) {
		let value = parseQTY(input.val());
		let min = parseFloat(input.attr('min'));
		if(value > min) {
			let new_value = getNewValue(input, false);
			if(new_value < min) {
				new_value = parseQTY(min);
			}
			input.val(new_value).change();
		}
	}

	function plusQuantity(input) {
		let value = parseFloat(input.val());
		let max = parseFloat(input.attr('max'));
		if(Number.isNaN(max) || value < max) {
			let new_value = getNewValue(input, true);
			if(new_value > max) {
				new_value = parseQTY(max);
			}
			input.val(new_value).change();
		}
	}

	function parseQTY(qty) {
		let qty_parts = Number.parseFloat(qty).toFixed(2).toString().split('');
		qty_parts.reverse();

		let precision = 2;
		qty_parts.some(function (item, index) {
			if(item !== "0") {
				precision = Number.parseInt(precision - index);
				return true;
			}
			return false;
		});

		return Number(parseFloat(qty).toFixed(precision));
	}
});