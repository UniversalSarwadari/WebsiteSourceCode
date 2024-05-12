<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Integration;

use Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service,
	Barn2\Plugin\WC_Variation_Prices\Plugin\Handlers\Price_Range;

/**
 * Handles the integration with WooCommerce Variation Prices
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Variation_Prices implements Registerable, Service {

	/**
	 * Register the class
	 *
	 * @since 2.0.0
	 */
	public function register() {
		// Integrate with WooCommerce Quick View Pro
		add_filter( 'wc_bulk_variations_get_cell_price_range_html', [ $this, 'get_price_range' ], 10, 2 );
	}

	/**
	 * If WooCommerce Variation Prices is installed
	 * get the price range for a subset of variation ids
	 *
	 * @param string $price_html The original price tag
	 * @param array[int] $variation_ids The list of variation product ids
	 *
	 * @return string
	 */
	public function get_price_range( $price_html, $variation_ids ) {
		if ( class_exists( 'Barn2\Plugin\WC_Variation_Prices\Plugin\Handlers\Price_Range' ) ) {
			// TODO: add the integration with WVP
			$price_html = $price_html;
		}

		return $price_html;
	}

}
