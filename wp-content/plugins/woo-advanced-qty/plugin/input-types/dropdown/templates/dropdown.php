<?php
	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\QuantityController;
?>
<div class="drop-down-button <?php echo is_cart() ? 'on-cart-page-drop-down' : ''; ?>">
	<div class="quantity margin-top-drop-down">
		<select id="qty" class="qty" name="<?php echo esc_attr($input_name); ?>" title="<?php echo esc_attr_x('Quantity', 'woocommerce') ?>">
			<?php foreach(QuantityController::getValidQuantityList($args) as $key => $value) : ?>
				<option value="<?php echo esc_attr($key); ?>" <?php echo QuantityController::bccomp($input_value, $key) === 0? 'selected' : ''; ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>