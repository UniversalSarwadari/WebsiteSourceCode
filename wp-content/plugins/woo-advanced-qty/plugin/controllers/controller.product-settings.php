<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class ProductSettingsController extends Controller {

	protected function registerFiltersAdmin() {
		parent::registerFiltersAdmin();
		Loader::addFilter('woocommerce_product_data_tabs', static::class, 'addProductSettingsTab');
	}

	protected function registerActionsAdmin() {
		parent::registerActionsAdmin();
		Loader::addAction('woocommerce_product_data_panels', static::class, 'displayProductSettingsTab');
		Loader::addAction('save_post', static::class, 'saveProductSettings', 10, 2);
	}

	/**
	 * Add tab for WooCommerce Advanced Quantity settings under products
	 *
	 * @since 1.4.0 Initial added
	 * @since 3.0.0 Moved to this class and added build in class filter to hide if grouped produc, instead of functionality to do so
	 *
	 * @param $product_tabs
	 *
	 * @return mixed
	 */
	public static function addProductSettingsTab($product_tabs) {
		// Add product tab
		$product_tabs['woo_advanced_qty'] = array(
			'label'  => __('WooCommerce Advanced Quantity', 'woo-advanced-qty'),
			'target' => 'woo_advanced_qty',
			'class'  => array('hide_if_grouped'),
			'priority' => 100
		);

		return $product_tabs;
	}

	/**
	 * Show options on every single product
	 *
	 * @since 1.0.0 Initial added
	 * @since 3.0.0 Moved to this class and moved content to template
	 */
	public static function displayProductSettingsTab() {
		Plugin::getTemplate('settings/product-settings-tab', array(
			'post_id' => $GLOBALS['thepostid']
		));
	}

	/**
	 * Save product options when saving post
	 *
	 * @since 1.0.0 Initial added
	 * @since 1.0.0 Moved to this class
	 *
	 * @param $post_id
	 * @param $post
	 */
	public static function saveProductSettings($post_id, $post) {
		// Make sure we are editing a product
		if((isset($_POST['action']) && $_POST['action'] !== 'editpost') || // is action not editpost
			is_ajax() || // is ajax call
			!is_admin() || // is not admin area
			defined('DOING_AUTOSAVE') || // is autosave
			empty($post_id) || // no post id
			empty($post) || // no post
			is_int(wp_is_post_revision($post)) || // is revision
			is_int(wp_is_post_autosave($post)) || // is autosave
			(isset($_POST['action']) && $_POST['action'] == 'inline-save') || // is inline edit
			(isset($_GET['woocommerce_bulk_edit']) && $_GET['woocommerce_bulk_edit'] == 1) || // is bulk edit
			(isset($_POST['action']) && $_POST['action'] === 'woocommerce_do_ajax_product_import') || // is product import
			(isset($_GET['action']) && $_GET['action'] === 'process' && isset($_GET['page']) && $_GET['page'] === 'pmxi-admin-import') // is product import

		) {
			return;
		}

		// Min Quantity
		if(isset($_REQUEST['advanced-qty-min']) && $_REQUEST['advanced-qty-min'] > 0) {
			update_post_meta($post_id, static::getSettingName('min'), $_REQUEST['advanced-qty-min']);
		} else {
			delete_post_meta($post_id, static::getSettingName('min'));
		}

		// Quantity Step
		if(isset($_REQUEST['advanced-qty-step']) && $_REQUEST['advanced-qty-step'] > 0) {
			update_post_meta($post_id, static::getSettingName('step'), $_REQUEST['advanced-qty-step']);
		} else {
			delete_post_meta($post_id, static::getSettingName('step'));
		}

		// Max Quantity
		if(isset($_REQUEST['advanced-qty-max']) && $_REQUEST['advanced-qty-max'] > 0) {
			update_post_meta($post_id, static::getSettingName('max'), $_REQUEST['advanced-qty-max']);
		} else {
			delete_post_meta($post_id, static::getSettingName('max'));
		}

		// Quantity Value
		if(isset($_REQUEST['advanced-qty-value']) && $_REQUEST['advanced-qty-value'] > 0) {
			update_post_meta($post_id, static::getSettingName('value'), $_REQUEST['advanced-qty-value']);
		} else {
			delete_post_meta($post_id, static::getSettingName('value'));
		}

		// Price Suffix
		if(isset($_REQUEST['advanced-qty-price-suffix']) && !empty($_REQUEST['advanced-qty-price-suffix'])) {
			update_post_meta($post_id, static::getSettingName('price-suffix'), $_REQUEST['advanced-qty-price-suffix']);
		} else {
			delete_post_meta($post_id, static::getSettingName('price-suffix'));
		}

		// Quantity Suffix
		if(isset($_REQUEST['advanced-qty-quantity-suffix']) && !empty($_REQUEST['advanced-qty-quantity-suffix'])) {
			update_post_meta($post_id, static::getSettingName('quantity-suffix'), $_REQUEST['advanced-qty-quantity-suffix']);
		} else {
			delete_post_meta($post_id, static::getSettingName('quantity-suffix'));
		}

		// Price factor
		if(isset($_REQUEST['advanced-qty-price-factor']) && !empty($_REQUEST['advanced-qty-price-factor'])) {
			update_post_meta($post_id, static::getSettingName('price-factor'), $_REQUEST['advanced-qty-price-factor']);
		} else {
			delete_post_meta($post_id, static::getSettingName('price-factor'));
		}

		// Step Intervals
		if(isset($_REQUEST['advanced-qty-step-intervals']) && !empty($_REQUEST['advanced-qty-step-intervals'])) {
			$step_intervals = StepIntervalsController::convertStringToArray($_REQUEST['advanced-qty-step-intervals']);
			update_post_meta($post_id, static::getSettingName('step-intervals'), $step_intervals);
		} else {
			delete_post_meta($post_id, static::getSettingName('step-intervals'));
		}

		// Quantity Input Picker
		if(isset($_REQUEST['advanced-qty-input-picker']) && !empty($_REQUEST['advanced-qty-input-picker']) && $_REQUEST['advanced-qty-input-picker'] != 'default-input') {
			update_post_meta($post_id, static::getSettingName('input-picker'), $_REQUEST['advanced-qty-input-picker']);
		} else {
			delete_post_meta($post_id, static::getSettingName('input-picker'));
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
	protected static function getSettingName($identifier) {
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
	public static function getSetting($product_id, $identifier, $default = null) {
		$setting = get_post_meta($product_id, static::getSettingName($identifier), true);

		if(empty($setting)) {
			$setting = $default;
		}

		return apply_filters('morningtrain/woo-advanced-qty/settings/product/getSetting', $setting, $identifier, $default);
	}
}