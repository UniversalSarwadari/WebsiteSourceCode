<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class ProductVariationsController extends Controller {

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addFilter('woocommerce_available_variation', static::class, 'availableVariationData', 100, 3);
	}

	protected function registerActions() {
		parent::registerActions();

		Loader::addAction('wp_enqueue_scripts', static::class, 'enqueueVariationsScript');
	}

	public static function enqueueVariationsScript() {
		if(!is_product()) {
			return;
		}

		$product = wc_get_product();

		if(empty($product) || !$product->is_type('variable')) {
			return;
		}

		Plugin::addScript('variations');
	}

	/**
	 * Add variation data to the array
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $variation_data
	 * @param $product
	 * @param $variation
	 *
	 * @return array
	 */
	public static function availableVariationData($variation_data, $product, $variation) {
		$args = InputArgsController::applyArgs(array(), $variation);

		$variation_data = array_merge($args, $variation_data);

		$variation_data['min_qty'] = $args['min_value'];
		$variation_data['max_qty'] = isset($args['max_value']) ? $args['max_value'] : $variation_data['max_qty'];

		if(isset($variation_data['input_type']) && in_array($variation_data['input_type'], array('slider', 'dropdown'))) {
			$variation_data['valid_values'] = QuantityController::getValidQuantityList($args);
		}

		return $variation_data;
	}
}