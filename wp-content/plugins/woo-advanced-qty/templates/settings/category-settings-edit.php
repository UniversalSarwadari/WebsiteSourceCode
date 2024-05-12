<?php

	use \Morningtrain\WooAdvancedQTY\Plugin\Controllers\StepIntervalsController;
	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\InputTypesController;
?>
<tr>
	<th>
		<h2><?php _e('Advanced Quantity', 'woo-advanced-qty'); ?></h2>
	</th>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-qty-min"><?php _e('Minimum', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<input type="number" name="advanced-qty-min" id="advanced-qty-min" value="<?php echo get_option('product-category-advanced-qty-min-' . $term_id); ?>" step="0.01" min="0">
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-qty-max"><?php _e('Maximum', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<input type="number" name="advanced-qty-max" id="advanced-qty-max" value="<?php echo get_option('product-category-advanced-qty-max-' . $term_id); ?>" step="0.01" min="0">
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-qty-step"><?php _e('Step', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<input type="number" name="advanced-qty-step" id="advanced-qty-step" value="<?php echo get_option('product-category-advanced-qty-step-' . $term_id); ?>" step="0.01" min="0">
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-qty-step-intervals"><?php _e('Step intervals', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<input type="text" name="advanced-qty-step-intervals" id="advanced-qty-step-intervals" value="<?php echo StepIntervalsController::convertArrayToString(get_option('product-category-advanced-qty-step-intervals-' . $term_id)); ?>" placeholder="<?php _e('Example: 0,10,5|10,100,10', 'woo-advanced-qty'); ?>">
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-qty-value"><?php _e('Standard value', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<input type="number" name="advanced-qty-value" id="advanced-qty-value" value="<?php echo get_option('product-category-advanced-qty-value-' . $term_id); ?>" step="0.01" min="0">
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-price-suffix"><?php _e('Price suffix', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<input type="text" name="advanced-qty-price-suffix" id="advanced-qty-price-suffix" value="<?php echo get_option('product-category-advanced-qty-price-suffix-' . $term_id); ?>">
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-quantity-suffix"><?php _e('Quantity suffix', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<input type="text" name="advanced-qty-quantity-suffix" id="advanced-qty-quantity-suffix" value="<?php echo get_option('product-category-advanced-qty-quantity-suffix-' . $term_id); ?>">
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-price-factor"><?php _e('Display Price Factor', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<input type="number" name="advanced-qty-price-factor" id="advanced-qty-price-factor" value="<?php echo get_option('product-category-advanced-qty-price-factor-' . $term_id); ?>" step="0.01" min="0">
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="advanced-input-picker"><?php _e('Input picker', 'woo-advanced-qty'); ?></label>
	</th>
	<td>
		<select name="advanced-qty-input-picker" id="advanced-qty-input-picker">
			<?php
				$type = get_option('product-category-advanced-qty-input-picker-' . $term_id);
				foreach(InputTypesController::getInputTypesList() as $val => $input) {
					?>
					<option
						value="<?php echo $val; ?>"<?php echo $type == $val ? 'selected' : ''; ?>><?php echo $input; ?></option>
					<?php
				}
			?>
		</select>
	</td>
</tr>