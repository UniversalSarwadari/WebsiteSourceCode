<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class LegacyController extends Controller {

	/**
	 * Changes settings values
	 *
	 * @since 3.0.0 added ['input-picker']['drop-down-input'] and ['input-picker']['slider']
	 *
	 * @var array
	 */
	static $legacy_setting_values = array(
		'input-picker' => array(
			'drop-down-input' => 'dropdown',
			'slider-input' => 'slider'
		)
	);

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addFilter('woocommerce_order_amount_item_subtotal', static::class, 'itemTotalFix', 10, 5);
		Loader::addFilter('woocommerce_order_amount_item_total', static::class, 'itemTotalFix', 10, 5);

		Loader::addFilter('morningtrain/woo-advanced-qty/settings/product/getSetting', static::class, 'convertSettingsValue', 10, 2);
		Loader::addFilter('morningtrain/woo-advanced-qty/settings/category/getSetting', static::class, 'convertSettingsValue', 10, 2);
		Loader::addFilter('morningtrain/woo-advanced-qty/settings/global/getSetting', static::class, 'convertSettingsValue', 10, 2);
	}

	/**
	 * Get WooCommerce Version
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @return bool|string
	 */
	public static function getWooCommerceVersion() {
		if(class_exists('WooCommerce')) {
			global $woocommerce;

			return $woocommerce->version;
		}

		return false;
	}

	/**
	 * Checks the version of WooCommerce
	 *
	 * @since 2.4.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param string $version
	 *
	 * @return bool
	 */
	public static function checkWooCommerceVersion($version, $operator = '>=') {
		return version_compare(static::getWooCommerceVersion(), $version, $operator);
	}

	/**
	 * Fixed issue when quantity is lower than 1 on older woocommerce version. Is solved in WooCommerce version 3.3.0
	 *
	 * @since 1.0.0 Initial added for total
	 * @since 2.2.0 Initial added for subtotal
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $total
	 * @param $order
	 * @param $item
	 * @param $inc_tax
	 * @param $round
	 *
	 * @return float|int|string
	 */
	public static function itemTotalFix($total, $order, $item, $inc_tax, $round) {
		if(static::checkWooCommerceVersion('3.3.0', '<')) {
			if(is_object($item) && is_callable(array($item, 'get_subtotal')) && is_callable(array($item, 'get_quantity')) && is_callable(array($item, 'get_subtotal_tax'))) {
				if($item->get_quantity() < 1) {
					$total = 0;

					if($inc_tax) {
						$total = ($item->get_subtotal() + $item->get_subtotal_tax()) / $item->get_quantity();
					} else {
						$total = ($item->get_subtotal() / $item->get_quantity());
					}
					$total = $round ? number_format((float) $total, wc_get_price_decimals(), '.', '') : $total;
				}
			} else {
				if($item['item_meta']['_qty'][0] < 1) {
					if($inc_tax) {
						$price = ($item['line_subtotal'] + $item['line_subtotal_tax']) / $item['item_meta']['_qty'][0];
					} else {
						$price = ($item['line_subtotal'] / $item['item_meta']['_qty'][0]);
					}

					$total = $round ? number_format((float) $price, wc_get_price_decimals(), '.', '') : $price;
				}
			}
		}

		return $total;
	}

	/**
	 * Some setting values has changed, make it backwards compatible
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $value
	 * @param $setting_name
	 *
	 * @return mixed
	 */
	public static function convertSettingsValue($value, $setting_name) {
		if(isset(static::$legacy_setting_values[$setting_name][$value])) {
			return static::$legacy_setting_values[$setting_name][$value];
		}

		return $value;
	}
}