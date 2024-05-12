<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class InputArgsController extends Controller {

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addFilter('woocommerce_quantity_input_args', static::class, 'applyArgs', 100, 2);

		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'applyMin', 10, 2);
		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'applyStep', 20, 2);
		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'applyMax', 30, 2);
		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'applyStandardValue', 40, 3);
		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'applyPriceFactor', 45, 3);
		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'applyMiscArgs', 50, 3);
		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'modifyArgsBasedOnCartContent', 60, 3);
		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'validateInputArgs', 100, 1);

		// Apply step to quantity input in backend
		Loader::addFilter('woocommerce_quantity_input_step', static::class, 'adminApplyArgsStep', 10, 2);
	}

	/**
	 * Apply args to quantity input args array
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved to this class and splitted functionality
	 *
	 * @param      $args
	 * @param int|\WC_Product $product
	 * @param bool $cart_input
	 *
	 * @return mixed|void
	 */
	public static function applyArgs($args, $product, $cart_input = false) {
		// Do not apply to composite products
		if(isset($args['is_composite_products'])) {
			return $args;
		}

		if(!is_a($product, 'WC_Product')) {
			$product = wc_get_product($product);

			if(empty($product)) {
				return $args;
			}
		}

		// if is cart page and is product in cart
		if(!$cart_input && CartController::isProductInCart($product->get_id()) && is_cart()) {
			$cart_input = true;
		}

		$cache_key = $product->get_id() . '_' . ($cart_input ? 'cart_' : '') . 'input_args_' . md5(json_encode($args));

		$cached = wp_cache_get($cache_key, Plugin::getTextDomain());

		if($cached) {
			return $cached;
		}

		// Apply args
		$input_args = apply_filters('morningtrain/woo-advanced-qty/input-args', $args, $product, $cart_input);

		wp_cache_set($cache_key, $input_args, plugin::getTextDomain());

		return $input_args;
	}

	/**
	 * Appply min arg to args array
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved out of another function
	 *
	 * @param array $args
	 * @param $product
	 *
	 * @return array
	 */
	public static function applyMin($args, $product) {
		$min_value = SettingsController::getAppliedSettingForProduct($product->get_id(), 'min');
		if($min_value !== null) {
			$args['min_value'] = $min_value;
		} else if(!isset($args['min_value'])) {
			$args['min_value'] = 1;
		}

		return $args;
	}

	/**
	 * Apply Step arg to args array
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved out of another function
	 *
	 * @param $args
	 * @param $product
	 *
	 * @return mixed
	 */
	public static function applyStep($args, $product) {
		$step_value = SettingsController::getAppliedSettingForProduct($product->get_id(), 'step');
		if($step_value !== null) {
			$args['step'] = $step_value;
		} else if(!isset($args['step_value'])) {
			$args['step'] = 1;
		}

		return $args;
	}

	/**
	 * Apply Max arg to args array
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved out of another function
	 *
	 * @param  array    $args
	 * @param \WC_Product $product
	 * @param bool $cart_input
	 *
	 * @return mixed
	 */
	public static function applyMax($args, $product) {
		$max_value = SettingsController::getAppliedSettingForProduct($product->get_id(), 'max');
		if($max_value > 0) {
			$args['max_value'] = $max_value;
		}

		// Make sure it can not be more than in stock, if manage stock and backorders not allowed
		if(isset($args['max_value']) && $args['max_value'] > 0 && $product->managing_stock() && !$product->backorders_allowed()) {
			$stock_qty = $product->get_stock_quantity();

			if($args['max_value'] > $stock_qty) {
				$args['max_value'] = $stock_qty;
			}
		}

		return $args;
	}

	/**
	 * Apply standard value to input args
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved out of another function
	 *
	 * @param      $args
	 * @param      $product
	 * @param bool $cart_input
	 *
	 * @return mixed
	 */
	public static function applyStandardValue($args, $product, $cart_input = false) {
		if(!$cart_input) {
			$input_value = SettingsController::getAppliedSettingForProduct($product->get_id(), 'value');

			if(!empty($input_value)) {
				$args['input_value'] = $input_value;
			} else if(isset($args['min_value'])) {
				$args['input_value'] =  $args['min_value'];
			} else {
				$args['input_value'] = 1;
			}
		}

		return $args;
	}

	/**
	 * Apply display price factor
	 * @param       $args
	 * @param       $product
	 * @param false $cart_input
	 *
	 * @return mixed
	 */
	public static function applyPriceFactor($args, $product, $cart_input = false) {
		$price_factor = SettingsController::getAppliedSettingForProduct($product->get_id(), 'price-factor');

		if($price_factor > 0 && $price_factor != 1) {
			$args['price_factor'] = $price_factor;
		}

		return $args;
	}

	/**
	 * Apply extra args to input args
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved out of another function
	 *
	 * @param $args
	 * @param $product
	 *
	 * @return mixed
	 */
	public static function applyMiscArgs($args, $product, $cart_input) {
		$args['pattern'] = '[0-9]+([,.][0-9]+)?';
		$args['inputmode'] = 'numeric';
		$args['product_id'] = $product->get_id();
		$args['input_type'] = SettingsController::getAppliedSettingForProduct($product->get_id(), 'input-picker', 'default');

		if($cart_input) {
			$input_type = GlobalSettingsController::getSetting('cart-page-input-picker', 'product_setting');

			if($input_type !== 'product_setting') {
				$args['input_type'] = $input_type;
			}
		}

		if(DevicesController::isMobileDevice() && GlobalSettingsController::getSetting('force-dropdown-mobile', false) === 'yes') {
			$args['input_type'] = 'dropdown';
		}

		return $args;
	}

	/**
	 * Modify input args if the product is in cart
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved out of another function
	 *
	 * @param      $args
	 * @param      $product
	 * @param bool $cart_input
	 *
	 * @return mixed
	 */
	public static function modifyArgsBasedOnCartContent($args, $product, $cart_input = false) {
		// Change min, max and value if we already have the product in cart
		if(!$cart_input && CartController::isProductInCart($product->get_id())) {
			$in_cart_count = CartController::getProductQuantityInCart($product->get_id());
			// Max value
			if(isset($args['max_value']) && $args['max_value'] > 0) {
				$args['max_value'] = (float) $args['max_value'] - $in_cart_count;
				if($args['max_value'] < 0) {
					$args['max_value'] = 0;
				}
			}

			// Min
			if(!isset($args['min_value'])) {
				$args['min_value'] = 1;
			}
			$args['min_value'] = $args['min_value'] - $in_cart_count;
			if($args['min_value'] <= 0) {
				if(isset($args['max_value']) && is_numeric($args['max_value']) && $args['max_value'] >= 0 && ($args['max_value'] === 0 || (isset($args['step']) && $args['max_value'] < $args['step']))) {
					$args['min_value'] = 0;
				} else if(isset($args['step'])) {
					$args['min_value'] = $args['step'];
				} else {
					$args['min_value'] = 1;
				}
			}

			// Input value
			$args['input_value'] = $args['min_value'];
		}

		return $args;
	}

	/**
	 * Validate input args
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved out of another function
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function validateInputArgs($args) {
		// min can not be less than 0
		if(!isset($args['min_value'])) {
			$args['min_value'] = 1;
		}
		if($args['min_value'] < 0) {
			$args['min_value'] = 0;
		}

		// Step can not be less than 0.01
		if(!isset($args['step'])) {
			$args['step'] = 1;
		}
		if($args['step'] < 0.01) {
			$args['step'] = 0.01;
		}

		// if max isset, max must pass the validate function
		if(isset($args['max_value']) && $args['max_value'] > 0) {
			$args['max_value'] = QuantityController::parseQuantity($args['max_value'], $args);
		}

		// input value must pass the validate function
		if(!isset($args['input_value'])) {
			$args['input_value'] = $args['min_value'];
		}
		$args['input_value'] = QuantityController::parseQuantity($args['input_value'], $args);

		return $args;
	}

	/**
	 * Set stpe in backend order edit quantity input
	 *
	 * @since 1.4.1 Initial added
	 * @since 3.0.0 Moved to this class and using step setting for product instead of just 0.01
	 *
	 * @param $step
	 * @param $product
	 *
	 * @return float
	 */
	public static function adminApplyArgsStep($step, $product) {
		if(is_admin()) {
			$step = 0.01; // Has to be 0.01 to not conflict with step intervals and allow the admin to totally customize the quantity in backend
		}

		return $step;
	}
}