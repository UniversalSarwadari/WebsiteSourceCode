<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class QuantitySuffixesController extends Controller {

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addFilter('woocommerce_checkout_cart_item_quantity', static::class, 'displayOnOrderReview', 10, 2);

		Loader::addFilter('woocommerce_order_item_quantity_html', static::class, 'displayOnOrderComplete', 10, 2);
		Loader::addFilter('woocommerce_email_order_item_quantity', static::class, 'displayOnOrderComplete', 10, 2);

		Loader::addFilter('woocommerce_cart_item_quantity', static::class, 'displayOnCart', 10, 3);
	}

	protected function registerActions() {
		parent::registerActions();

		Loader::addAction('woocommerce_after_template_part', static::class, 'displayOnSingleProduct');
	}

	/**
	 * Add suffix between quantity and buy button
	 *
	 * @since 2.0.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $template_name
	 */
	public static function displayOnSingleProduct($template_name) {
		if($template_name === 'global/quantity-input.php') {
			global $post;

			$suffix = SettingsController::getAppliedSettingForProduct($post->ID, 'quantity-suffix');

			if(!empty($suffix)) {
				Plugin::getTemplate('partials/span', array(
					'text' => $suffix,
					'class' => 'qty-suffix-float qty-suffix'
				));
			}
		}
	}

	/**
	 * Adds quantity suffix to order review during checkout process
	 *
	 * @since 2.2.7 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param string $qty
	 * @param array  $cart_item
	 *
	 * @return string
	 */
	public static function displayOnOrderReview($qty, $cart_item) {
		if(is_array($cart_item) && isset($cart_item['product_id'])) {
			$suffix = SettingsController::getAppliedSettingForProduct($cart_item['product_id'], 'quantity-suffix');

			if(!empty($suffix)) {
				$qty .= Plugin::getTemplate('partials/span', array(
					'text' => $suffix,
					'class' => 'woo-adv-qty-suffix'
				), false);
			}
		}

		return $qty;
	}

	/**
	 * Adds quantity suffix to order complete (after a order is placed) and order email
	 *
	 * @since 2.2.7 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param string      $qty
	 * @param \WC_Product $item
	 *
	 * @return string
	 */
	public static function displayOnOrderComplete($qty, $item) {
		if(is_object($item)) {
			$suffix = SettingsController::getAppliedSettingForProduct($item->get_product_id(), 'quantity-suffix');

			if(!empty($suffix)) {
				$qty .= Plugin::getTemplate('partials/span', array(
					'text' => ' ' . $suffix,
					'class' => array(
						'woo-adv-qty-completed_order_suffix'
					)
				), false);
			}
		}

		return $qty;
	}

	/**
	 * Show quantity suffix on the cart page
	 *
	 * @since 2.2.2 Initial added
	 * @since 2.2.2 Moved to this class
	 *
	 * @param $product_quantity
	 * @param $cart_item_key
	 * @param $cart_item
	 *
	 * @return string
	 */
	public static function displayOnCart($product_quantity, $cart_item_key, $cart_item = false) {
		if(is_cart() && GlobalSettingsController::getSetting('show-quantity-suffix-on-cart-page') === 'yes') {
			if(!$cart_item) {
				$cart_items = WC()->cart->get_cart();

				if(!empty($cart_items) && array_key_exists($cart_item_key, $cart_items)) {
					$cart_item = $cart_items[$cart_item_key];
				}
			}
			if(!isset($cart_item['product_id'])) {
				return $product_quantity;
			}

			$suffix = SettingsController::getAppliedSettingForProduct($cart_item['product_id'], 'quantity-suffix');

			if(!empty($suffix)) {
				$product_quantity .= Plugin::getTemplate('partials/span', array(
					'text' => $suffix,
					'class' => 'cart-quantity-suffix'
				), false);
			}
		}

		return $product_quantity;
	}
}