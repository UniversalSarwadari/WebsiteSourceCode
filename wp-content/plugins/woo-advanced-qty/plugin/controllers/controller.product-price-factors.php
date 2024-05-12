<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class ProductPriceFactorsController extends Controller {

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addAction('woocommerce_get_price_html', static::class, 'manipulatePriceHtml', 10, 2);


		// Variations
		Loader::addFilter('woocommerce_variation_prices', static::class, 'applyProductPriceFactorForVariationsDisplay', 10, 3);
		Loader::addFilter('woocommerce_get_variation_prices_hash', static::class, 'manipulateVariationPriceHash', 10, 3);


		// Cart display
		Loader::addFilter('woocommerce_cart_product_price', static::class, 'manipulateCartPriceHtml', 10, 2);
	}

	/**
	 * Manipulate Cart Price Html by running it again with extra filters
	 * @param $price_html
	 * @param $product
	 *
	 * @return string
	 */
	public static function manipulateCartPriceHtml($price_html, $product) {
		if(!is_admin() && static::productHasPriceFactor($product)) {
			Loader::removeFilter('woocommerce_cart_product_price', static::class, 'manipulateCartPriceHtml', 10);
			Loader::addFilter('woocommerce_get_price_including_tax', static::class, 'applyProductPriceFactorToPrice', 10, 3);
			Loader::addFilter('woocommerce_get_price_excluding_tax', static::class, 'applyProductPriceFactorToPrice', 10, 3);
			$price_html = WC()->cart->get_product_price($product);
			Loader::addFilter('woocommerce_cart_product_price', static::class, 'manipulateCartPriceHtml', 10, 2);
			Loader::removeFilter('woocommerce_get_price_including_tax', static::class, 'applyProductPriceFactorToPrice', 10);
			Loader::removeFilter('woocommerce_get_price_excluding_tax', static::class, 'applyProductPriceFactorToPrice', 10);

		}

		return $price_html;
	}

	/**
	 * Fixes a problem with for_display not part of hash
	 *
	 * @param $hash
	 * @param $product
	 * @param $for_display
	 *
	 * @return mixed
	 */
	public static function manipulateVariationPriceHash($hash, $product, $for_display) {
		$hash['for_display'] = $for_display;

		return $hash;
	}

	/**
	 * Apply product factor on variation display price
	 *
	 * @param $prices
	 * @param $product
	 * @param $for_display
	 *
	 * @return mixed
	 *
	 */
	public static function applyProductPriceFactorForVariationsDisplay($prices, $product, $for_display) {
		if($for_display) {
			if(!empty($prices['price'])) {
				foreach($prices['price'] as $product_id => &$price) {
					$price = static::applyProductPriceFactorToPrice($price,1, $product_id);
				}
			}
			if(!empty($prices['regular_price'])) {
				foreach($prices['regular_price'] as $product_id => &$price) {
					$price = static::applyProductPriceFactorToPrice($price,1, $product_id);
				}
			}
			if(!empty($prices['sale_price'])) {
				foreach($prices['sale_price'] as $product_id => &$price) {
					$price = static::applyProductPriceFactorToPrice($price, 1, $product_id);
				}
			}
		}

		return $prices;
	}

	/**
	 * Manipulate Price HTML by running it again with extra filters on price functions
	 *
	 * @param $price_html
	 * @param $product
	 *
	 * @return mixed
	 */
	public static function manipulatePriceHtml($price_html, $product) {
		if(static::productHasPriceFactor($product)) {
			Loader::removeFilter('woocommerce_get_price_html', static::class, 'manipulatePriceHtml', 10);
			Loader::addFilter('woocommerce_get_price_including_tax', static::class, 'applyProductPriceFactorToPrice', 10, 3);
			Loader::addFilter('woocommerce_get_price_excluding_tax', static::class, 'applyProductPriceFactorToPrice', 10, 3);
			$price_html = $product->get_price_html();
			Loader::addFilter('woocommerce_get_price_html', static::class, 'manipulatePriceHtml', 10, 2);
			Loader::removeFilter('woocommerce_get_price_including_tax', static::class, 'applyProductPriceFactorToPrice', 10);
			Loader::removeFilter('woocommerce_get_price_excluding_tax', static::class, 'applyProductPriceFactorToPrice', 10);

		}

		return $price_html;
	}

	/**
	 * Check if product needs price factor to be applied
	 *
	 * @param $product
	 *
	 * @return bool
	 */
	public static function productHasPriceFactor($product) {
		$args = InputArgsController::applyArgs(array(), $product);

		return isset($args['price_factor']);
	}

	/**
	 * Apply price factor
	 * @param $return_price
	 * @param $qty
	 * @param $product
	 *
	 * @return float|int
	 */
	public static function applyProductPriceFactorToPrice($return_price, $qty, $product) {
		$args = InputArgsController::applyArgs(array(), $product);

		if(!empty($args['price_factor'])) {
			return (string) $return_price * $args['price_factor'];
		}

		return $return_price;
	}
}