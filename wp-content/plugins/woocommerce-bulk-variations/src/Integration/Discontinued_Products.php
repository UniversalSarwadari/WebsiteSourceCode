<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Integration;

use Barn2\WBV_Lib\Registerable,
	Barn2\Plugin\WC_Discontinued_Products\Plugin;

/**
 * Handles the integration with WooCommerce Discontinued Products
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Discontinued_Products implements Registerable {

	/**
	 * Register the class
	 *
	 * @since 2.0.0
	 */
	public function register() {
		if ( class_exists( 'Barn2\Plugin\WC_Discontinued_Products\Plugin' ) ) {
			add_filter( 'wc_bulk_variations_is_variation_visible', [ $this, 'is_variation_visible' ], 10, 2 );
			add_filter( 'wc_bulk_variations_script_params', [ $this, 'script_params' ] );
		}
	}

	/**
	 * If WooCommerce Discontinued Products is installed
	 * determines whether a variation is publicly visible
	 * depending on its discontinued status
	 *
	 * @param bool $is_public Whether the variation is public or not
	 * @param WC_Product_Variation $variation The product variation object
	 *
	 * @return bool
	 */
	public function is_variation_visible( $is_visible, $variation ) {
		if ( $variation->get_stock_status() === Plugin::DISCONTINUED_ID && ! $variation->is_purchasable() && 'no' === get_option( 'wcdp_hide_from_store' ) ) {
			return true;
		}

		return $is_visible;
	}

	public function script_params( $params ) {
		$params['hide_discontinued'] = filter_var( get_option( 'wcdp_hide_from_store' ), FILTER_VALIDATE_BOOLEAN );

		return $params;
	}

}
