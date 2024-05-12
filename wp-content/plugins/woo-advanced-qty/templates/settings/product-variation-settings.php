<?php
	/**
	 * @var $loop
	 * @var $variation_data
	 * @var $variation
	 * @var $post_id
	 */

	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\StepIntervalsController;
	use Morningtrain\WooAdvancedQTY\Plugin\Controllers\ProductVariationSettingsController;
?>
<div class="show_if_individually_variation_control" style="display: none;">
	<?php
		$value = ProductVariationSettingsController::getSetting($variation->ID, 'min');

		// Minimum
		woocommerce_wp_text_input(array(
			'id'                => "advanced-qty-min_$loop",
			'name'              => "advanced-qty-min[$loop]",
			'label'             => __('Minimum', 'woo-advanced-qty'),
			'desc_tip'          => 'true',
			'description'       => __('The minimum quantity a customer has to order of this variation.', 'woo-advanced-qty'),
			'value'             => ProductVariationSettingsController::getSetting($variation->ID, 'min', ''),
			'type'              => 'number',
			'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			'wrapper_class' => 'form-row form-row-first',
		));

		// Maximum
		woocommerce_wp_text_input(array(
			'id'                => "advanced-qty-max_$loop",
			'name'              => "advanced-qty-max[$loop]",
			'label'             => __('Maximum', 'woo-advanced-qty'),
			'desc_tip'          => 'true',
			'description'       => __('The maximum quantity a customer can add to same order of this variation.', 'woo-advanced-qty'),
			'value'             => ProductVariationSettingsController::getSetting($variation->ID, 'max', ''),
			'type'              => 'number',
			'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			'wrapper_class' => 'form-row form-row-last',
		));

		// Step
		woocommerce_wp_text_input(array(
			'id'                => "advanced-qty-step_$loop",
			'name'              => "advanced-qty-step[$loop]",
			'label'             => __('Step', 'woo-advanced-qty'),
			'desc_tip'          => 'true',
			'description'       => __('The step between allowed quantities.', 'woo-advanced-qty'),
			'value'             => ProductVariationSettingsController::getSetting($variation->ID, 'step', ''),
			'type'              => 'number',
			'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			'wrapper_class' => 'form-row form-row-first',
		));

		// Step intervals
		woocommerce_wp_text_input(array(
			'id'                => "advanced-qty-step-intervals_$loop",
			'name'              => "advanced-qty-step-intervals[$loop]",
			'label'             => __('Step intervals', 'woo-advanced-qty'),
			'desc_tip'          => 'true',
			'placeholder'       => __('Example: 0,10,5|10,100,10', 'woo-advanced-qty'),
			'custom_attributes' => array('value' => '12'),
			'description'       => __('The step between allowed quantities intervals (example: 0,10,5|10,100,10) which means from 0 to 10 it will increase by 5 and 10 to 100 will increase with 10', 'woo-advanced-qty'),
			'value'             => StepIntervalsController::convertArrayToString(ProductVariationSettingsController::getSetting($variation->ID, 'step-intervals', array())),
			'type'              => 'text',
			'wrapper_class' => 'form-row form-row-last',
		));

		// Standard value
		woocommerce_wp_text_input(array(
			'id'                => "advanced-qty-value_$loop",
			'name'              => "advanced-qty-value[$loop]",
			'label'             => __('Standard value', 'woo-advanced-qty'),
			'desc_tip'          => 'true',
			'description'       => __('The standard value the quantity fields shows.', 'woo-advanced-qty'),
			'value'             => ProductVariationSettingsController::getSetting($variation->ID, 'value', ''),
			'type'              => 'number',
			'custom_attributes' => array('step' => '0.01', 'min' => '0'),
			'wrapper_class' => 'form-row form-row-full',
		));
	?>
</div>