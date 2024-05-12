<?php
	/**
	 * @var int $post_id
	 */

	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\StepIntervalsController;
	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\InputTypesController;
	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\ProductSettingsController;
?>
<div id="woo_advanced_qty" class="panel woocommerce_options_panel">
	<div class="options_group">
		<?php
			// Minimum
			woocommerce_wp_text_input(array(
				'id'                => 'advanced-qty-min',
				'label'             => __('Minimum', 'woo-advanced-qty'),
				'desc_tip'          => 'true',
				'description'       => __('The minimum quantity a customer has to order of this product.', 'woo-advanced-qty'),
				'value'             => ProductSettingsController::getSetting($post_id, 'min'),
				'type'              => 'number',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			));

			// Maximum
			woocommerce_wp_text_input(array(
				'id'                => 'advanced-qty-max',
				'label'             => __('Maximum', 'woo-advanced-qty'),
				'desc_tip'          => 'true',
				'description'       => __('The maximum quantity a customer can add to same order of this product.', 'woo-advanced-qty'),
				'value'             => ProductSettingsController::getSetting($post_id, 'max'),
				'type'              => 'number',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			));

			// Step
			woocommerce_wp_text_input(array(
				'id'                => 'advanced-qty-step',
				'label'             => __('Step', 'woo-advanced-qty'),
				'desc_tip'          => 'true',
				'description'       => __('The step between allowed quantities.', 'woo-advanced-qty'),
				'value'             => ProductSettingsController::getSetting($post_id, 'step'),
				'type'              => 'number',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			));

			// Step intervals
			woocommerce_wp_text_input(array(
				'id'                => 'advanced-qty-step-intervals',
				'label'             => __('Step intervals', 'woo-advanced-qty'),
				'desc_tip'          => 'true',
				'placeholder'       => __('Example: 0,10,5|10,100,10', 'woo-advanced-qty'),
				'custom_attributes' => array('value' => '12'),
				'description'       => __('The step between allowed quantities intervals (example: 0,10,5|10,100,10) which means from 0 to 10 it will increase by 5 and 10 to 100 will increase with 10', 'woo-advanced-qty'),
				'value'             => StepIntervalsController::convertArrayToString(ProductSettingsController::getSetting($post_id, 'step-intervals')),
				'type'              => 'text',
			));

			// Standard value
			woocommerce_wp_text_input(array(
				'id'                => 'advanced-qty-value',
				'label'             => __('Standard value', 'woo-advanced-qty'),
				'desc_tip'          => 'true',
				'description'       => __('The standard value the quantity fields shows.', 'woo-advanced-qty'),
				'value'             => ProductSettingsController::getSetting($post_id, 'value'),
				'type'              => 'number',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			));
		?>
	</div>
	<div class="options_group">
		<?php
			// Price suffix
			woocommerce_wp_text_input(array(
				'id'          => 'advanced-qty-price-suffix',
				'label'       => __('Price suffix', 'woo-advanced-qty'),
				'desc_tip'    => 'true',
				'description' => __('Text to add after price (example: pr. 100g)', 'woo-advanced-qty'),
				'value'       => ProductSettingsController::getSetting($post_id, 'price-suffix', ''),
				'type'        => 'text',
			));

			// Quantity suffix
			woocommerce_wp_text_input(array(
				'id'          => 'advanced-qty-quantity-suffix',
				'label'       => __('Quantity suffix', 'woo-advanced-qty'),
				'desc_tip'    => 'true',
				'description' => __('Text to add after chosen quantity (example: x 100 g)', 'woo-advanced-qty'),
				'value'       => ProductSettingsController::getSetting($post_id, 'quantity-suffix', ''),
				'type'        => 'text',
			));

			// Price factor
			Woocommerce_wp_text_input(array(
				'id' => 'advanced-qty-price-factor',
				'label' => __('Display Price Factor', 'woo-advanced-qty'),
				'desc_tip' => 'true',
				'description' => __('Factor to multiply the display price. Example: Can be used for displaying prices for kilos while the quantity follows grams', 'woo-advanced-qty'),
				'value' => ProductSettingsController::getSetting($post_id, 'price-factor'),
				'type' => 'number',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			));
		?>
	</div>
	<div class="options_group">
		<?php
			// Input picker
			woocommerce_wp_select(array(
				'id'          => 'advanced-qty-input-picker',
				'label'       => __('Input picker', 'woo-advanced-qty'),
				'desc_tip'    => 'true',
				'description' => __('The input picker that controls how the quantity is increased/decreased', 'woo-advanced-qty'),
				'value'       => ProductSettingsController::getSetting($post_id, 'input-picker', 'default'),
				'type'        => 'text',
				'options'     => InputTypesController::getInputTypesList(),
			));
		?>
	</div>
</div>