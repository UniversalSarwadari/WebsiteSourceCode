<?php
	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\QuantityController;

	$valid_values = function_exists( 'wc_esc_json' ) ? wc_esc_json( json_encode(QuantityController::getValidQuantityList($args)) ) : _wp_specialchars( json_encode(QuantityController::getValidQuantityList($args)), ENT_QUOTES, 'UTF-8', true );
?>
<div class="slider-input<?php echo !is_single($product_id) ? ' smaller-slider' : ''; echo is_cart() ? ' full-width-slider' : ''; ?>">
	<div class="quantity">
		<div class="slider">
			<div class="custom-handle ui-slider-handle" data-format="<?php echo isset($format) ? $format : ''; ?>"></div>
		</div>
		<input type="hidden" id="amount" class="qty" value="<?php echo esc_attr($input_value); ?>" name="<?php echo esc_attr($input_name); ?>" title="<?php echo esc_attr_x('Quantity', 'woo-advanced-qty') ?>" pattern="<?php echo esc_attr($pattern); ?>" max-value="<?php echo esc_attr($max_value); ?>" inputmode="<?php echo esc_attr($inputmode); ?>" min-value="<?php echo esc_attr($min_value); ?>" step="<?php echo esc_attr($step) ?>" data-valid_values="<?php echo $valid_values; ?>">
	</div>
</div>