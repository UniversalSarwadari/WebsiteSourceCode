<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Cassandra\Type\Set;
use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class PriceSuffixesController extends Controller {

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addFilter('woocommerce_get_price_suffix', static::class, 'display', 10, 2);

		Loader::addFilter('woocommerce_cart_item_price', static::class, 'displayOnCart', 10, 3);
	}

	/**
	 * Add suffix to price
	 *
	 * @since 2.4.4 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $suffix
	 * @param $product
	 *
	 * @return string
	 */
	public static function display($suffix, $product) {
		$_suffix = SettingsController::getAppliedSettingForProduct($product->get_id(), 'price-suffix');

		if(!empty($_suffix)) {
			$suffix .= Plugin::getTemplate('partials.small', array(
				'text' => $_suffix,
				'class' => 'woocommerce-price-suffix'
			), false);
		}

		return $suffix;
	}

	/**
	 * Show price suffix on the cart page
	 *
	 * @since 2.2.2 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $product_price
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return string
	 */
	public static function displayOnCart($product_price, $cart_item, $cart_item_key) {
		if(is_cart() && GlobalSettingsController::getSetting('show-price-suffix-on-cart-page') === 'yes') {
			$_suffix = SettingsController::getAppliedSettingForProduct($cart_item['product_id'], 'price-suffix');

			if(!empty($_suffix)) {
				$product_price .= Plugin::getTemplate('partials.span', array(
					'text' => $_suffix,
					'class' => 'cart-price-suffix'
				), false);
			}
		}

		return $product_price;
	}
}