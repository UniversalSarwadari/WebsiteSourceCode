<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Handlers;

use Barn2\Plugin\WC_Bulk_Variations\Args,
	Barn2\Plugin\WC_Bulk_Variations\Util\Util,
	Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service;

use function \Barn2\Plugin\WC_Bulk_Variations\wbv;

/**
 * This class handles our bulk variations table shortcode.
 *
 * Example usage:
 *   [bulk_variations
 *       include="10"
 *       columns/horizontal="name"
 *       rows/vertical="t-shirts",
 *       images="false",
 *       lightbox="true"
 *       disable_purchasing="false"
 *       show_stock="true"]
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Shortcode implements Registerable, Service {

	const SHORTCODE = 'bulk_variations';

	public function register() {
		add_shortcode( self::SHORTCODE, [ __CLASS__, 'do_shortcode' ] );
	}

	/**
	 * Handles our product table shortcode.
	 *
	 * @param array $atts The attributes passed in to the shortcode
	 * @param string $content The content passed to the shortcode (not used)
	 * @return string The shortcode output
	 */
	public static function do_shortcode( $atts, $content = '' ) {
		// Pre process variations table args
		$atts = self::setup_product_data( $atts );

		if ( false === $atts ) {
			// translators: the ID of a product
			return __( 'The product ID is missing or the ID provided does not correspond to a variable product.', 'woocommerce-bulk-variations' );
		}

		$atts['class'] = isset( $atts['class'] ) ? $atts['class'] : '';

		$global_product = null;

		if ( isset( $GLOBALS['product'] ) && is_a( $GLOBALS['product'], 'WC_Product' ) ) {
			// if the global $product object is set
			// store it in a temporary variable...
			$global_product = $GLOBALS['product'];
		}

		// set the global product with the one used in the grid
		$GLOBALS['product'] = wc_get_product( $atts['include'] );

		// Return the table as HTML
		$output = apply_filters( 'wc_bulk_variations_shortcode_output', wc_get_bulk_variations_table( $atts ), $atts );

		// $theme_compat = wbv()->get_service( 'integration\theme_compat' );
		// $styles       = $theme_compat->get_theme_vars_styles();
		// $classes      = $theme_compat->get_theme_classes();

		// disables $theme_compat styling
		$styles  = '';
		$classes = [];

		$classes = implode(
			' ',
			array_merge(
				[ 'wc-bulk-variations-table-wrapper' ],
				apply_filters(
					'wc_bulk_variations_product_classes',
					$classes,
					$atts['include']
				)
			)
		);

		if ( ! is_null( $global_product ) ) {
			// if the global product object was set
			// restore it as a global product
			$GLOBALS['product'] = $global_product;
		}

		return sprintf(
			'<div class="%2$s" %3$s>%1$s</div>',
			$output,
			$classes,
			$styles
		);
	}

	/**
	 * Prepare the shortocde parameters
	 * conditionally using the global and product settings
	 *
	 * @param array $atts The
	 * @return void
	 */
	private static function setup_product_data( $atts ) {
		global $product;

		if ( ! $atts ) {
			// in a shortcode with no parameters
			// $atts is an empty string instead of an empty array
			// let's fix that!
			$atts = [];
		}

		if ( ! isset( $atts['include'] ) && is_a( $product, 'WC_Product_Variable' ) ) {
			// the `include` parameter is missing but the current product in the loop
			// is a variable product
			// let's use that
			$atts['include'] = $product->get_id();
		}

		$_product = wc_get_product( $atts['include'] );

		if ( ! is_a( $_product, 'WC_Product_Variable' ) ) {
			return false;
		}

		$atts = array_map(
			function( $a ) {
				if ( in_array( $a, [ 'true', 'yes', 'on', '1', 'false', 'no', 'off', '0' ], true ) ) {
					return filter_var( $a, FILTER_VALIDATE_BOOLEAN );
				}

				return $a;
			},
			$atts
		);

		// if `disable_purchasing` is not set in the shortcode
		// force it to `false` so that the shortcode can be used on product pages
		// when the product is set to disable purchasing
		if ( ! isset( $atts['disable_purchasing'] ) ) {
			$atts['disable_purchasing'] = false;
		}

		if ( isset( $atts['horizontal'] ) && ! isset( $atts['columns'] ) ) {
			$atts['columns'] = $atts['horizontal'];
			unset( $atts['horizontal'] );
		}

		if ( isset( $atts['vertical'] ) && ! isset( $atts['rows'] ) ) {
			$atts['rows'] = $atts['vertical'];
			unset( $atts['vertical'] );
		}

		$product_attributes = $_product->get_attributes();

		if ( $product_attributes ) {
			$product_attributes = array_keys( $product_attributes );

			if ( isset( $atts['columns'] ) && in_array( "pa_{$atts['columns']}", $product_attributes, true ) ) {
				$atts['columns'] = 'pa_' . $atts['columns'];
			}

			if ( isset( $atts['rows'] ) && in_array( "pa_{$atts['rows']}", $product_attributes, true ) ) {
				$atts['rows'] = 'pa_' . $atts['rows'];
			}
		}

		if ( isset( $atts['images'] ) && ! isset( $atts['variation_images'] ) ) {
			$atts['variation_images'] = $atts['images'];
			unset( $atts['images'] );
		}

		if ( isset( $atts['variation_images'] ) ) {
			if ( is_bool( $atts['variation_images'] ) && $atts['variation_images'] ) {
				$atts['variation_images'] = 'row';
			}
		}

		if ( isset( $atts['stock'] ) && ! isset( $atts['show_stock'] ) ) {
			$atts['show_stock'] = filter_var( $atts['stock'], FILTER_VALIDATE_BOOLEAN );
			unset( $atts['stock'] );
		}

		if ( is_a( $product, 'WC_Product_Variable' ) ) {
			$variations_data      = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA, false );
			$variations_structure = Settings::get_setting( Settings::OPTION_VARIATIONS_STRUCTURE, false );

			$override = get_post_meta( $_product->get_id(), Settings::OPTION_VARIATIONS_DATA . '_override', true );

			if ( $override ) {
				$variations_data      = wp_parse_args( get_post_meta( $_product->get_id(), Settings::OPTION_VARIATIONS_DATA, true ), $variations_data );
				$variations_structure = wp_parse_args( get_post_meta( $_product->get_id(), Settings::OPTION_VARIATIONS_STRUCTURE, true ), $variations_structure );
			}

			$atts = wp_parse_args( $atts, array_merge( $variations_data, $variations_structure ) );
		}

		return shortcode_atts( Args::get_defaults(), $atts, self::SHORTCODE );
	}
}
// class WC_Product_Table_Shortcode
