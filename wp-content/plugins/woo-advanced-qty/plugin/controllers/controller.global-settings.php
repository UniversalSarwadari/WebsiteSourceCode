<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class GlobalSettingsController extends Controller {

	protected function registerActionsAdmin() {
		parent::registerActionsAdmin();

		Loader::addAction('woocommerce_settings_tabs_array', static::class, 'addSettingsTab', 99);
		Loader::addAction('woocommerce_settings_tabs_advanced_quantity', static::class, 'displaySettings', 99);

		Loader::addAction('woocommerce_update_options_advanced_quantity', static::class, 'saveSettings');
	}

	protected function registerFiltersAdmin() {
		parent::registerFiltersAdmin();

		Loader::addFilter('plugin_action_links_woo-advanced-qty/woo-advanced-qty.php', static::class, 'displayPluginSettingsLink');
	}

	/**
	 * Displays the settings link on the plugin page
	 *
	 * @since 2.4.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public static function displayPluginSettingsLink($links) {
		// Compose settings link
		$settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=advanced_quantity') . '">' . __('Settings', 'woo-advanced-qty') . '</a>';
		// Display settings link before deactivate
		array_unshift($links, $settings_link);

		return $links;
	}

	/**
	 * Adds a new settings tab in WooCommerce settings
	 *
	 * @since 2.3.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $setting_tabs
	 *
	 * @return mixed
	 */
	public static function addSettingsTab($setting_tabs) {
		$setting_tabs['advanced_quantity'] = __('Advanced Quantity', 'woo-advanced-qty');

		return $setting_tabs;
	}

	/**
	 * Gets the settings and displays it
	 * in the settings tab
	 *
	 * @since 2.3.0 Initial added
	 * @since 3.0.0 Moved to this class
	 */
	public static function displaySettings() {
		woocommerce_admin_fields(static::getSettingFields());
	}
	
	/**
	 * General/Global plugin settings
	 *
	 * @since 2.3.0
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function getSettingFields() {
		$product_settings = array(
			'section_title'           => array(
				'title' => __('Advanced Quantity', 'woo-advanced-qty'),
				'type'  => 'title',
				'id'    => 'advanced_qty_options_product',
				'desc'  => __('Set to "0" to deactivate the global values.', 'woo-advanced-qty'),
			),
			'setting_minimum'         => array(
				'name'              => __('Minimum', 'woo-advanced-qty'),
				'desc'              => __('This controls the minimum a customer can add to an order of products', 'woo-advanced-qty'),
				'desc_tip'          => true,
				'id'                => 'woo-advanced-qty-min',
				'type'              => 'number',
				'css'               => 'min-width: 100px;',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
				'default'           => 0,
			),
			'setting_maximum'         => array(
				'name'              => __('Maximum', 'woo-advanced-qty'),
				'desc'              => __('This controls the maximum a customer can add to an order of a product.', 'woo-advanced-qty'),
				'desc_tip'          => true,
				'id'                => 'woo-advanced-qty-max',
				'type'              => 'number',
				'css'               => 'min-width: 100px;',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
				'default'           => 0,
			),
			'setting_step'            => array(
				'name'              => __('Step', 'woo-advanced-qty'),
				'desc'              => __('This controls the way that quantity increments.', 'woo-advanced-qty'),
				'desc_tip'          => true,
				'id'                => 'woo-advanced-qty-step',
				'type'              => 'number',
				'css'               => 'min-width: 100px;',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
				'default'           => 0,
			),
			'setting_step_intervals'  => array(
				'name'        => __('Step intervals', 'woo-advanced-qty'),
				'desc'        => __('Example: 0,10,5|10,100,10 which means from 0 to 10 it will increase by 5 and 10 to 100 will increase with 10.', 'woo-advanced-qty'),
				'desc_tip'    => true,
				'id'          => 'woo-advanced-qty-step-intervals-unformated',
				'type'        => 'text',
				'placeholder' => 'Example: 0,10,5|10,100,10',
				'css'         => 'min-width: 100px;',
				'default'     => 0,
			),
			'setting_standard_value'  => array(
				'name'              => __('Standard value', 'woo-advanced-qty'),
				'desc'              => __('This controls the standard value for the quantity fields.', 'woo-advanced-qty'),
				'desc_tip'          => true,
				'id'                => 'woo-advanced-qty-value',
				'type'              => 'number',
				'css'               => 'min-width: 100px;',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
				'default'           => 0,
			),
			'setting_price_suffix'    => array(
				'name'     => __('Price suffix', 'woo-advanced-qty'),
				'desc'     => __('This controls the price suffix.', 'woo-advanced-qty'),
				'desc_tip' => true,
				'id'       => 'woo-advanced-qty-price-suffix',
				'type'     => 'text',
				'css'      => 'min-width: 100px;',
				'default'  => 0,
			),
			'setting_quantity_suffix' => array(
				'name'     => __('Quantity suffix', 'woo-advanced-qty'),
				'desc'     => __('This controls the quantity suffix fields.', 'woo-advanced-qty'),
				'desc_tip' => true,
				'id'       => 'woo-advanced-qty-quantity-suffix',
				'type'     => 'text',
				'css'      => 'min-width: 100px;',
				'default'  => 0,
			),
			'setting_price_factor'  => array(
				'name'              => __('Display Price Factor', 'woo-advanced-qty'),
				'desc'              => __('Factor to multiply the display price. Example: Can be used for displaying prices for kilos while the quantity follows grams', 'woo-advanced-qty'),
				'desc_tip'          => true,
				'id'                => 'woo-advanced-qty-price-factor',
				'type'              => 'number',
				'css'               => 'min-width: 100px;',
				'custom_attributes' => array('step' => '0.01', 'min' => '0'),
				'default'           => 0,
			),
			'setting_input_picker'    => array(
				'name'     => __('Input picker', 'woo-advanced-qty'),
				'desc'     => __('This controls the input type for the quantity field.', 'woo-advanced-qty'),
				'desc_tip' => true,
				'id'       => 'woo-advanced-qty-input-picker',
				'type'     => 'select',
				'options'  => InputTypesController::getInputTypesList(true),
				'css'      => 'min-width: 100px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'advanced_qty_options_product',
			),
		);

		$cart_settings = array(
			'cart_section_title'                 => array(
				'title' => __('Cart', 'woo-advanced-qty'),
				'type'  => 'title',
				'id'    => 'advanced_qty_options_cart',
				'desc'  => __('Settings for the cart page', 'woo-advanced-qty'),
			),
			'setting_price_suffix_cart'          => array(
				'name' => __('Price suffix', 'woo-advanced-qty'),
				'desc' => __('Show the price suffix on the cart page.', 'woo-advanced-qty'),
				'id'   => 'woo-advanced-qty-show-price-suffix-on-cart-page',
				'type' => 'checkbox',
				'css'  => 'min-width: 100px;',
			),
			'setting_quantity_suffix_cart'       => array(
				'name' => __('Quantity suffix', 'woo-advanced-qty'),
				'desc' => __('Show the quantity suffix on the cart page.', 'woo-advanced-qty'),
				'id'   => 'woo-advanced-qty-show-quantity-suffix-on-cart-page',
				'type' => 'checkbox',
				'css'  => 'min-width: 100px;',
			),
			'setting_input_picker_cart'          => array(
				'name'     => __('Input picker', 'woo-advanced-qty'),
				'desc'     => __('This controls the input type for the quantity field.', 'woo-advanced-qty'),
				'desc_tip' => true,
				'id'       => 'woo-advanced-qty-cart-page-input-picker',
				'type'     => 'select',
				'options'  => array_merge(array('product_setting' => __('Follow the product setting', 'woo-advanced-qty')), InputTypesController::getInputTypesList(true)),
				'default' => 'product_setting',
				'css'      => 'min-width: 100px',
			),
			'setting_auto_cart_refresh'          => array(
				'name'          => __('Automatic cart refresh', 'woo-advanced-qty'),
				'desc'          => __('Automatically update cart on quantity change', 'woo-advanced-qty'),
				'id'            => 'woo-advanced-qty-triggers-auto-cart-refresh',
				'type'          => 'checkbox',
				'css'           => 'min-width: 100px',
				'checkboxgroup' => 'start',
			),
			'setting_hide_update_button'         => array(
				'desc'          => __('Hide update button', 'woo-advanced-qty'),
				'desc_tip'      => __('Only a good idea if Automatic cart refresh is activated or something else handles cart refresh', 'woo-advanced-qty'),
				'id'            => 'woo-advanced-qty-hide-update-button',
				'type'          => 'checkbox',
				'css'           => 'min-width: 100px',
				'checkboxgroup' => 'end',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'advanced_qty_options_cart',
			),
		);

		$misc_settings = array(
			'misc_section_title'                 => array(
				'title' => __('Misc', 'woo-advanced-qty'),
				'type'  => 'title',
				'id'    => 'advanced_qty_options_misc',
				'desc'  => __('Miscellaneous settings', 'woo-advanced-qty'),
			),
			'setting_force_mobile_dropdown' => array(
				'name'     => __('Dropdown on mobile', 'woo-advanced-qty'),
				'desc'     => __('Force dropdown input for mobile, to avoid invalid quantity selection', 'woo-advanced-qty'),
				'id'       => 'woo-advanced-qty-force-dropdown-mobile',
				'type'     => 'checkbox',
				'css'      => 'min-width: 100px',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'advanced_qty_options_misc',
			),
		);

		return array_merge($product_settings, $cart_settings, $misc_settings);
	}

	/**
	 * Updates the settings
	 *
	 * @since 2.3.0 Initial added
	 * @since 3.0.0 Moved to this class
	 */
	public static function saveSettings() {
		woocommerce_update_options(static::getSettingFields());
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
	protected static function getSettingName($identifier) {
		$prefix = 'woo-advanced-qty-';

		return $prefix . $identifier;
	}

	/**
	 * Get setting for global settings based on a identifier (ex. min, step or max)
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param      $identifier
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public static function getSetting($identifier, $default = null) {
		$setting = get_option(static::getSettingName($identifier));

		if(empty($setting)) {
			$setting = $default;
		}

		return apply_filters('morningtrain/woo-advanced-qty/settings/global/getSetting', $setting, $identifier, $default);
	}
}