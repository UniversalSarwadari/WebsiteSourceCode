<?php
	/**
	 * @var int       $product_id
	 * @var int|float $step
	 * @var string    $intervals
	 * @var int|float $min_value
	 * @var int|float $max_value
	 * @var string    $input_name
	 * @var int|float $input_value
	 * @var string    $pattern
	 * @var string    $inputmode
	 */
?>
<div class="plus-minus-button">
	<div class="quantity <?php echo !is_single($product_id) ? 'smaller-minus-plus' : ''; ?> <?php echo is_cart() ? 'on-cart-plus-minus-button' : ''; ?>">
		<input type='button' value='-' class='woo-advanced-minus'/>
		<input type="text" id="qty" class="plus-minus-input qty" step="<?php echo esc_attr($step); ?>"
		       min="<?php echo esc_attr($min_value); ?>" max="<?php echo esc_attr($max_value); ?>"
		       name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($input_value); ?>"
		       title="<?php echo esc_attr_x('Quantity', 'woo-advanced-qty') ?>"
		       <?php echo isset($step_intervals) ? 'data-step_intervals="' . esc_attr(json_encode($step_intervals)) . '"' : ''; ?>
		       pattern="<?php echo esc_attr($pattern); ?>" inputmode="<?php echo esc_attr($inputmode); ?>" data-product_id="<?php echo esc_attr($product_id); ?>"/>
		<input type='button' value='+' class='woo-advanced-plus'/>
	</div>
</div>