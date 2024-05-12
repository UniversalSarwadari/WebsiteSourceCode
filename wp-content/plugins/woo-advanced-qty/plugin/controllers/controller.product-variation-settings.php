<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class ProductVariationSettingsController extends Controller {

	protected function registerActionsAdmin() {
		parent::registerActionsAdmin();

		Loader::addAction('woocommerce_product_after_variable_attributes', static::class, 'addVariationSettings', 100, 3);
		Loader::addAction('woocommerce_variation_options', static::class, 'addVariationOption', 100, 3);

		Loader::addAction('woocommerce_save_product_variation', static::class, 'saveVariationSettings', 10, 2);

		Loader::addAction('woocommerce_variable_product_bulk_edit_actions', static::class, 'addVariationBulkAction',100);
		Loader::addAction('woocommerce_bulk_edit_variations_default', static::class, 'bulkActionToggleIndividuallyVariationControl', 10, 4);
	}

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addFilter('morningtrain/woo-advanced-qty/settings/getAppliedSettingForProduct', static::class, 'getAppliedSettingForVariation', 10, 5);
	}

	/**
	 * Add applied setting to variation
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param       $setting_value
	 * @param       $product_id
	 * @param       $setting_identifier
	 * @param null  $default
	 * @param array $ignore
	 *
	 * @return mixed|null
	 */
	public static function getAppliedSettingForVariation($setting_value, $product_id, $setting_identifier, $default = null, $ignore = array('global-input')) {
		if(!empty($setting_value)) {
			return $setting_value;
		}

		$product = wc_get_product($product_id);

		if(!empty($product) && $product->is_type('variation')) {
			if(static::getSetting($product_id, 'individually_variation_control')) {
				$setting_value = static::getSetting($product_id, $setting_identifier);
			}

			if(empty($setting_value)) {
				$setting_value = ProductSettingsController::getSetting($product->get_parent_id(), $setting_identifier);
			}
		}

		return $setting_value;
	}

	/**
	 * Bulk toggle individually varition control quantity
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $bulk_action
	 * @param $data
	 * @param $product_id
	 * @param $variations
	 */
	public static function bulkActionToggleIndividuallyVariationControl($bulk_action, $data, $product_id, $variations) {
		if(!$bulk_action === 'toggle_individually_variation_control') {
			return;
		}
		foreach ($variations as $variation_id ) {
			$prev_value = static::getSetting($variation_id, 'individually_variation_control');
			if(!$prev_value) {
				update_post_meta($variation_id, static::getSettingName('individually_variation_control'), true);
			} else {
				delete_post_meta($variation_id, static::getSettingName('individually_variation_control'));
			}
		}
	}

	/**
	 * Add bulk action to toggle individually varition control quantity
	 *
	 * @since 3.0.0 Initial added
	 */
	public static function addVariationBulkAction() {
		Plugin::getTemplate('settings/optiongroup', array(
			'label' => __('WooCommerce Advanced Quantity', 'woo-advanced-qty'),
			'options' => array(
				'toggle_individually_variation_control' => __('Toggle "WooCommerce Advanced Quantity"')
			)
		));
	}

	/**
	 * Add product variation option - toggl on/off individual qiantity control for the variation.
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 */
	public static function addVariationOption($loop, $variation_data, $variation) {
		Plugin::getTemplate('settings/product-variation-option', array(
			'loop' => $loop,
			'variation_data' => $variation_data,
			'variation' => $variation,
			'post_id' => $GLOBALS['thepostid']
		));
	}

	/**
	 * Add Variation Settings
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 */
	public static function addVariationSettings($loop, $variation_data, $variation) {
		Plugin::getTemplate('settings/product-variation-settings', array(
			'loop' => $loop,
			'variation_data' => $variation_data,
			'variation' => $variation,
			'post_id' => $GLOBALS['thepostid']
		));
	}

	public static function saveVariationSettings($variation_id, $index) {
		// Enabled individually varaition quantity control
		if(isset($_REQUEST['individually_variation_control'][$index]) && $_REQUEST['individually_variation_control'][$index] == 'on') {
			update_post_meta($variation_id, static::getSettingName('individually_variation_control'), true);
		} else {
			delete_post_meta($variation_id, static::getSettingName('individually_variation_control'));
		}


		// Min Quantity
		if(isset($_REQUEST['advanced-qty-min'][$index]) && $_REQUEST['advanced-qty-min'][$index] > 0) {
			update_post_meta($variation_id, static::getSettingName('min'), $_REQUEST['advanced-qty-min'][$index]);
		} else {
			delete_post_meta($variation_id, static::getSettingName('min'));
		}

		// Quantity Step
		if(isset($_REQUEST['advanced-qty-step'][$index]) && $_REQUEST['advanced-qty-step'][$index] > 0) {
			update_post_meta($variation_id, static::getSettingName('step'), $_REQUEST['advanced-qty-step'][$index]);
		} else {
			delete_post_meta($variation_id, static::getSettingName('step'));
		}

		// Max Quantity
		if(isset($_REQUEST['advanced-qty-max'][$index]) && $_REQUEST['advanced-qty-max'][$index] > 0) {
			update_post_meta($variation_id, static::getSettingName('max'), $_REQUEST['advanced-qty-max'][$index]);
		} else {
			delete_post_meta($variation_id, static::getSettingName('max'));
		}

		// Quantity Value
		if(isset($_REQUEST['advanced-qty-value'][$index]) && $_REQUEST['advanced-qty-value'][$index] > 0) {
			update_post_meta($variation_id, static::getSettingName('value'), $_REQUEST['advanced-qty-value'][$index]);
		} else {
			delete_post_meta($variation_id, static::getSettingName('value'));
		}

		// Step Intervals
		if(isset($_REQUEST['advanced-qty-step-intervals'][$index]) && !empty($_REQUEST['advanced-qty-step-intervals'][$index])) {
			$step_intervals = StepIntervalsController::convertStringToArray($_REQUEST['advanced-qty-step-intervals'][$index]);
			update_post_meta($variation_id, static::getSettingName('step-intervals'), $step_intervals);
		} else {
			delete_post_meta($variation_id, static::getSettingName('step-intervals'));
		}
	}

	/**
	 * Get setting name based on an identifier (ex. min, step or max)
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $identifier
	 *
	 * @return string
	 */
	public static function getSettingName($identifier) {
		$prefix = '_advanced-qty-';

		return $prefix . $identifier;
	}

	/**
	 * Get setting for product based on a identifier (ex. min, step or max)
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param      $product_id
	 * @param      $identifier
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public static function getSetting($variation_id, $identifier, $default = null) {
		$setting = get_post_meta($variation_id, static::getSettingName($identifier), true);

		if(empty($setting)) {
			$setting = $default;
		}

		return apply_filters('morningtrain/woo-advanced-qty/settings/product_variation/getSetting', $setting, $identifier, $default);
	}
}