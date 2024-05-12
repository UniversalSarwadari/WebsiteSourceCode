<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class QuantityController extends Controller {

	protected function registerFilters() {
		parent::registerFilters();

		// Add to cart url and button in archive pages
		Loader::addFilter('woocommerce_loop_add_to_cart_link', static::class, 'archiveAddToCartButtonLink', 10, 2);
		Loader::addFilter('woocommerce_product_add_to_cart_url', static::class, 'addToCartUrl', 10, 2);

		// Validate quantity when adding to cart
		Loader::addFilter('woocommerce_add_to_cart_validation', static::class, 'addToCartValidateQuantity', 10, 6);

		// Validate quantity when updating cart
		Loader::addFilter('woocommerce_update_cart_validation', static::class, 'updateCartValidateQuantity', 10, 4);

		// Is sold individually
		Loader::addFilter('woocommerce_is_sold_individually', static::class, 'isSoldIndividually', 10, 2);
		Loader::addFilter('woocommerce_add_to_cart_sold_individually_quantity', static::class, 'addToCartIndividuallyQuantity', 10, 5);
		Loader::addFilter('woocommerce_cart_item_quantity', static::class, 'showCorrectIndividuallyQuantity', 10, 2);

		// Stock amount validation
		Loader::removeFilter('woocommerce_stock_amount', null, 'intval');
		Loader::addFilter('woocommerce_stock_amount', static::class, 'floatvalRounded');
	}

	/**
	 * Floatval but with rounding to precision of 2
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $value
	 *
	 * @return false|float
	 */
	public static function floatvalRounded($value) {
		return round(floatval($value), 2);
	}

	/**
	 * Check if quantity belongs to the right pattern, if not it will be changed, so it fits
	 *
	 * @since 1.2.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param     $quantity
	 * @param int $min
	 * @param int $max
	 * @param int $step
	 *
	 * @return float|int
	 */
	public static function parseQuantity($quantity, $args) {
		$quantity = (float) $quantity;

		$args = apply_filters('morningtrain/woo-advanced-qty/quantity/parseQuantity/args', $args, $quantity);

		$min = (float) $args['min_value'];

		// check if not in right interval
		$step_args = apply_filters('morningtrain/woo-advanced-qty/quantity/parseQuantity/step_args', $args, $quantity);

		// if max isset and quantity is more than max
		if((isset($step_args['max_value']) && is_numeric($step_args['max_value']) && $step_args['max_value'] >= 0 && $quantity > $step_args['max_value']) ||
			(isset($args['max_value']) && is_numeric($args['max_value']) && $args['max_value'] >= 0 && $quantity > $args['max_value'])) {
			$quantity = (float) $args['max_value'];
		}

		$step = (float) $step_args['step'];
		$diff = fmod(($quantity - ((float) $step_args['min_value'])), $step);
		$diff = round($diff, 2);

		if($diff > 0.01 && $diff != $step) {
			// Try to round op to next step
			$_quantity = $quantity + ($step - $diff);

			// If max isset an quantity is more than max round down instead
			if((isset($step_args['max_value']) && is_numeric($step_args['max_value']) && $step_args['max_value'] >= 0 && $_quantity > $step_args['max_value']) ||
				(isset($args['max_value']) && is_numeric($args['max_value']) && $args['max_value'] >= 0 && $_quantity > $args['max_value'])) {
				$_quantity = $quantity - $diff;
			}

			$quantity = $_quantity;
		}

		// if less than min
		if($quantity <= $min) {
			$quantity = $min;
		}

		// cannot be less than 0
		if($quantity < 0) {
			$quantity = 0;
		}

		$quantity = apply_filters('morningtrain/woo-advanced-qty/quantity/parseQuantity/quantity', $quantity, $args);

		return static::formatQuantity($quantity);
	}

	/**
	 * Is quantity valid
	 *
	 * @since 1.5.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param      $quantity
	 * @param int  $min
	 * @param null $max
	 * @param int  $step
	 *
	 * @return bool
	 */
	public static function isValidQuantity($quantity, $args) {
		$_quantity = static::parseQuantity($quantity, $args);

		return $_quantity == $quantity;
	}

	/**
	 * Get list with valid quantities  (max is forced applied to avoid infinity)
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param int  $min
	 * @param null $max
	 * @param int  $step
	 *
	 * @return array
	 */
	public static function getValidQuantityList($args) {
		$values = array();
		// if max is not set, count entries instead
		if(!isset($args['max_value']) || !is_numeric($args['max_value']) || $args['max_value'] < 0) {
			$_value = $args['min_value'];
			$max_count = apply_filters('morningtrain/woo-advanced-qty/quantity/getValidQuantityList/max_count', 100, $args);
			for($i = 0; $i < $max_count; $i++) {
				$values[(string) static::formatQuantity($_value)] = static::formatQuantity($_value, 'STRING');

				$_value = $_value + $args['step'];
			}
		} else {
			for($_value = $args['min_value']; static::bccomp($_value, $args['max_value']) <= 0; $_value += $args['step']) {
				$values[(string) static::formatQuantity($_value)] = static::formatQuantity($_value, 'STRING');
			}
		}

		return apply_filters('morningtrain/woo-advanced-qty/quantity/getValidQuantityList/values', $values, $args);
	}

	/**
	 * Mimics the functionality of bccomp
	 *
	 * @param $value1
	 * @param $value2
	 *
	 * @return int
	 */
	public static function bccomp($value1, $value2, $scale = 2) {
		$value1 = round((float) $value1, $scale);
		$value2 = round((float) $value2, $scale);

		if($value1 === $value2) {
			return 0;
		}

		if($value1 > $value2) {
			return 1;
		}

		return -1;
	}

	/**
	 * Change quantity for archive add to cart button (for ajax requests)
	 *
	 * @since 1.5.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param string      $url
	 * @param \WC_Product $product
	 *
	 * @return mixed|string
	 */
	public static function archiveAddToCartButtonLink($link, $product) {
		$args = InputArgsController::applyArgs(array(), $product);

		$link = preg_replace('/data-quantity="[0-9]*"/', 'data-quantity="' . $args['input_value'] . '"', $link);

		return $link;
	}

	/**
	 * Change quantity for add to cart url
	 *
	 * @since 1.5.3 Initial added
	 * @since 1.5.3 Moved to this class
	 *
	 * @param string      $url
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public static function addToCartUrl($url, $product) {
		if(strpos($url, 'add-to-cart=')) {
			$args = InputArgsController::applyArgs(array(), $product);

			$url = preg_replace('/quantity=[0-9.]*/', "quantity={$args['input_value']}", $url);

			$url .= '&quantity=' . $args['input_value'];
		}

		return $url;
	}

	/**
	 * Check if add to cart quantity is valid
	 *
	 * @since 1.5.0 Initital added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param      $valid
	 * @param      $product_id
	 * @param      $quantity
	 * @param int  $variation_id
	 * @param null $variations
	 * @param null $cart_item_data
	 *
	 * @return bool
	 */
	public static function addToCartValidateQuantity($valid, $product_id, $quantity, $variation_id = 0, $variations = null, $cart_item_data = null) {
		if($variation_id > 0 && ProductVariationSettingsController::getSetting($variation_id, 'individually_variation_control')) {
			$product_id = $variation_id;
		}

		$args = InputArgsController::applyArgs(array(), $product_id);

		// If more than max
		if(isset($args['max_value']) && $args['max_value'] >= 0 && $quantity > $args['max_value']) {
			if(CartController::isProductInCart($product_id)) {
				$args_total = InputArgsController::applyArgs(array(), $product_id, true);
				\wc_add_notice(sprintf(__('The maximum quantity allowed to purchase of this product is %s. You already have %s in your cart.', 'woo-advanced-qty'), $args_total['max_value'], CartController::getProductQuantityInCart($product_id)), 'error');
			} else {
				\wc_add_notice(sprintf(__('The maximum quantity allowed to purchase of this product is %s.', 'woo-advanced-qty'), $args['max_value']), 'error');
			}
			return false;
		}

		// If less than min
		if($quantity < $args['min_value']) {
			\wc_add_notice(sprintf(__('You must purchase at least %s of this product.', 'woo-advanced-qty'), $args['min_value']), 'error');

			return false;
		}

		// Validate quantity to step
		$_quantity = static::parseQuantity($quantity, $args);
		if($_quantity != $quantity) {
			\wc_add_notice(sprintf(__('You did not add a valid quantity to the cart. Try to add %s instead.', 'woo-advanced-qty'), $_quantity), 'error');

			return false;
		}

		return $valid;
	}

	/**
	 * Vallidate quantity on update cart
	 *
	 * @since 2.2.1 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $valid
	 * @param $cart_item_key
	 * @param $cart_item_data
	 * @param $quantity
	 *
	 * @return bool
	 */
	public static function updateCartValidateQuantity($valid, $cart_item_key, $cart_item_data, $quantity) {
		if($quantity > 0) {
			$product_id = !empty($cart_item_data['variation_id']) ? $cart_item_data['variation_id'] : $cart_item_data['product_id'];

			$args = InputArgsController::applyArgs(array(), $product_id, true);

			// If more than max
			if(isset($args['max_value']) && $args['max_value'] >= 0 && $quantity > $args['max_value']) {
				\wc_add_notice(sprintf(__('The maximum quantity allowed to purchase of this product is %s.', 'woo-advanced-qty'), $args['max_value']), 'error');
				return false;
			}

			// If less than min
			if($quantity < $args['min_value']) {
				\wc_add_notice(sprintf(__('You must purchase at least %s of this product.', 'woo-advanced-qty'), $args['min_value']), 'error');
				return false;
			}

			// Validate quantity to step
			$_quantity = static::parseQuantity($quantity, $args);
			if($_quantity != $quantity) {
				\wc_add_notice(sprintf(__('You did not add a valid quantity to the cart. Try to add %s instead.', 'woo-advanced-qty'), $_quantity), 'error');
				return false;
			}
		}

		return $valid;
	}

	/**
	 * Check if min quantity equels maximum quantity and set the product to be sold individually
	 *
	 * @since 1.3.0 Initial added
	 * @since 1.5.7 Removed - dont know why?
	 * @since 1.3.0 Readded in this class
	 *
	 * @param bool $return
	 * @param int | \WC_Product $product
	 *
	 * @return bool
	 */
	public static function isSoldIndividually($return, $product) {
		$args = InputArgsController::applyArgs(array(), $product, true);

		if(isset($args['min_value']) && isset($args['max_value'])) {
			return $args['min_value'] == $args['max_value'];
		}

		return $return;
	}

	/**
	 * Set the correct quantity for individually products
	 *
	 * @since 1.3.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $qty_individually
	 * @param $qty
	 * @param $product_id
	 * @param $variation_id
	 * @param $cart_item_data
	 *
	 * @return mixed
	 */
	public static function addToCartIndividuallyQuantity($qty_individually, $qty, $product_id, $variation_id, $cart_item_data) {
		$args = InputArgsController::applyArgs(array(), $product_id, true);

		if(static::isSoldIndividually(false, $product_id)) {
			return $args['min_value'];
		}

		return $qty_individually;
	}

	/**
	 * WooCommerce bug fix - show the right quantity in the cart for products which is sold individually
	 *
	 * @since 1.3.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param      $qty_html
	 * @param      $cart_item_key
	 * @param null $cart_item
	 *
	 * @return string
	 */
	public static function showCorrectIndividuallyQuantity($qty_html, $cart_item_key, $cart_item = null) {
		if($cart_item == null) {
			$cart_item = WC()->cart->get_cart_item($cart_item_key);
		}

		if(isset($cart_item['data']) && $cart_item['data']->is_sold_individually()) {
			$qty_html = $cart_item['quantity'] . '<input type="hidden" name="cart[' . $cart_item_key . '][qty]" value="' . $cart_item['quantity'] . '" />';
		}

		return $qty_html;
	}

	/**
	 * Get number of decimal places with a precision of 2.
	 * @param $quantity
	 *
	 * @return int
	 */
	public static function getDecimalPlaces($quantity) {
		// We only work with a precision of 2 - cast to string
		$quantity = number_format($quantity, 2);

		$i = 2;

		foreach(array_reverse(str_split($quantity)) as $char) {
			if($char !== '0') {
				return $i;
			}
			$i--;
		}
	}

	/**
	 * Format quantity round to precision and format as int, float or string
	 *
	 * @param        $quantity
	 * @param string $type NUMERIC | STRING |
	 *
	 * @return float|string
	 */
	public static function formatQuantity($quantity, $type = 'NUMERIC') {
		$decimal_places = static::getDecimalPlaces($quantity);
		switch($type) {
			case "STRING":
				return number_format($quantity, $decimal_places, wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
				break;
			default:
				$quantity = round($quantity, $decimal_places);
				if($decimal_places == 0) {
					return (int) $quantity;
				}
				return (float) $quantity;
		}
	}
}