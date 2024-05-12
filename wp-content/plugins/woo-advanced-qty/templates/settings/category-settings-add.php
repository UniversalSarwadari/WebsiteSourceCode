<?php
	use \Morningtrain\WooAdvancedQTY\Plugin\Controllers\InputTypesController;
?>
<div>
	<h2><?php _e('Advanced Quantity', 'woo-advanced-qty'); ?></h2>
</div>
<div class="form-field">
	<label for="advanced-qty-min"><?php _e('Minimum', 'woo-advanced-qty'); ?></label>
	<input type="number" name="advanced-qty-min" id="advanced-qty-min" value="" step="0.01" min="0">
</div>
<div class="form-field">
	<label for="advanced-qty-max"><?php _e('Maximum', 'woo-advanced-qty'); ?></label>
	<input type="number" name="advanced-qty-max" id="advanced-qty-max" value="" step="0.01" min="0">
</div>
<div class="form-field">
	<label for="advanced-qty-step"><?php _e('Step', 'woo-advanced-qty'); ?></label>
	<input type="number" name="advanced-qty-step" id="advanced-qty-step" value="" step="0.01" min="0">
</div>
<div class="form-field">
	<label for="advanced-qty-step-intervals"><?php _e('Step intervals', 'woo-advanced-qty'); ?></label>
	<input type="text" name="advanced-qty-step-intervals" id="advanced-qty-step-intervals" placeholder="<?php echo _e('Example: 0,10,5|10,100,10', 'woo-advanced-qty'); ?>" value="">
</div>
<div class="form-field">
	<label for="advanced-qty-value"><?php _e('Standard value', 'woo-advanced-qty'); ?></label>
	<input type="number" name="advanced-qty-value" id="advanced-qty-value" value="" step="0.01" min="0">
</div>
<div class="form-field">
	<label for="advanced-qty-price-suffix"><?php _e('Price suffix', 'woo-advanced-qty'); ?></label>
	<input type="text" name="advanced-qty-price-suffix" id="advanced-qty-price-suffix" value="">
</div>
<div class="form-field">
	<label for="advanced-qty-quantity-suffix"><?php _e('Quantity suffix', 'woo-advanced-qty'); ?></label>
	<input type="text" name="advanced-qty-quantity-suffix" id="advanced-qty-quantity-suffix" value="">
</div>
<div class="form-field">
	<label for="advanced-qty-price-factor"><?php _e('Display Price Factor', 'woo-advanced-qty'); ?></label>
	<input type="number" name="advanced-qty-price-factor" id="advanced-qty-price-factor" value="" step="0.01" min="0">
</div>
<div class="form-field"><label for="advanced-qty-input-picker"><?php _e('Input picker', 'woo-advanced-qty'); ?></label>
	<select name="advanced-qty-input-picker" id="advanced-qty-input-picker">
		<?php
			foreach(InputTypesController::getInputTypesList() as $type => $input) {
				?>
				<option value="<?php echo $type; ?>"><?php echo $input; ?></option>
				<?php
			}
		?>
	</select>
</div>