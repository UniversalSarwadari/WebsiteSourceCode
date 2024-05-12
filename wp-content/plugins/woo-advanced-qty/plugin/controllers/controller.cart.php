<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class CartController extends Controller {

	protected function registerActions() {
		parent::registerActions();

		Loader::addAction('wp_enqueue_scripts', static::class, 'addAutoUpdateScript');
		Loader::addAction('wp_enqueue_scripts', static::class, 'addHideUpdateButtonStyle');
	}

	public static function addAutoUpdateScript() {
		$auto_update_setting = GlobalSettingsController::getSetting('triggers-auto-cart-refresh');

		if(!empty($auto_update_setting) && $auto_update_setting === 'yes') {
			Plugin::addScript('woo-advanced-qty-cart-refresh');
		}
	}

	public static function addHideUpdateButtonStyle() {
		$auto_update_setting = GlobalSettingsController::getSetting('hide-update-button');

		if(!empty($auto_update_setting) && $auto_update_setting === 'yes') {
			Plugin::addStyle('hide-update-button');
		}
	}

	/**
	 * Check if the product is already in the cart
	 *
	 * @since 1.5.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $product_id
	 *
	 * @return bool
	 */
	public static function isProductInCart($product_id) {
		return static::getProductQuantityInCart($product_id) > 0;
	}

	/**
	 * Get product quantity in cart
	 *
	 * @since 1.5.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param int $product_id
	 *
	 * @return float
	 */
	public static function getProductQuantityInCart($product_id) {
		$cart_content = static::getCartContents();

		if(is_array($cart_content) && !empty($cart_content)) {
			foreach($cart_content as $cart_item_key => $item) {
				$item_product_id = $item['data']->get_id();

				if($product_id == $item_product_id) {
					return $item['quantity'];
				}
			}
		}
	}

	/**
	 * Get Cart contents
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @return array
	 */
	public static function getCartContents() {
		if(LegacyController::checkWooCommerceVersion('3.2.0') && isset(WC()->cart)) {
			return WC()->cart->get_cart_contents();
		} else {
			global $woocommerce;

			if(isset($woocommerce->cart)) {
				return $woocommerce->cart->cart_contents;
			}
		}
	}
}