<?php
/**
 * Template functions for WooCommerce Bulk Variations.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_bulk_variations_table' ) ) {
	function wc_get_bulk_variations_table( $args = [] ) {

		// Create and return the table as HTML
		$grid = Barn2\Plugin\WC_Bulk_Variations\Grid_Factory::create( $args );
		return $grid->render();
	}
}

if ( ! function_exists( 'wc_the_bulk_variations_table' ) ) {
	function wc_the_bulk_variations_table( $args = [] ) {
		echo wc_get_bulk_variations_table( $args ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

add_filter( 'woocommerce_get_availability_text', 'ucfirst', 10 );
