<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Handlers;

use Barn2\Plugin\WC_Bulk_Variations\Args,
	Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service;

use function \Barn2\Plugin\WC_Bulk_Variations\wbv;

/**
 * This class handles our bulk variations table on the product page.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Variation_Table implements Registerable, Service {

	public function register() {
		// Remove not needed product fields
		add_action( 'wp', [ __CLASS__, 'remove_product_fields' ] );
	}

	public static function remove_product_fields() {
		global $post;

		if ( $post && 'product' === $post->post_type && is_product() ) {
			$global_settings = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA, false );

			$product_id = $post->ID;
			$product    = wc_get_product( $product_id );

			if ( is_a( $product, 'WC_Product_Variable' ) ) {
				$attributes      = $product->get_variation_attributes();
				$attribute_count = count( $attributes );

				$override = get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA . '_override', true );
				$enable   = $global_settings['enable'];

				if ( $attribute_count > 2 ) {
					$enable = $global_settings['enable_multivariation'];
				}

				if ( $override ) {
					$enable = get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA . '_enable', true );
				}

				if ( $enable ) {
					add_action( 'woocommerce_before_single_product', [ __CLASS__, 'hook_into_summary_actions' ], 1 );
				} else {
					$variations_data = get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA, true );

					if ( $override && isset( $variations_data['hide_add_to_cart'] ) && $variations_data['hide_add_to_cart'] ) {
						remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
					}
				}
			}
		}

	}

	public static function hook_into_summary_actions() {
		// if ( has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' ) ) {
		// 	$location = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );
		// 	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', $location );
		// 	add_action( 'woocommerce_single_product_summary', [ __CLASS__, 'print_variation_table' ], 21 );
		// } elseif ( has_action( 'woocommerce_single_product_summary_single_add_to_cart', 'woocommerce_template_single_add_to_cart' ) ) {
		// 	$location = has_action( 'woocommerce_single_product_summary_single_add_to_cart', 'woocommerce_template_single_add_to_cart' );
		// 	remove_action( 'woocommerce_single_product_summary_single_add_to_cart', 'woocommerce_template_single_add_to_cart', $location );
		// 	add_action( 'woocommerce_single_product_summary_single_add_to_cart', [ __CLASS__, 'print_variation_table' ] );
		// } else {
			remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
			add_action( 'woocommerce_variable_add_to_cart', [ __CLASS__, 'print_variation_table' ], 30 );
		// }
	}

	public static function print_variation_table() {
		global $product;
		$product_id = $product ? $product->get_id() : 0;

		if ( $product_id ) {
			$variations_data      = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA );
			$variations_structure = Settings::get_setting( Settings::OPTION_VARIATIONS_STRUCTURE );

			$override = get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA . '_override', true );

			if ( $override ) {
				$variations_data      = wp_parse_args( get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA, true ), $variations_data );
				$variations_structure = wp_parse_args( get_post_meta( $product_id, Settings::OPTION_VARIATIONS_STRUCTURE, true ), $variations_structure );
			}

			$atts = array_merge( $variations_data, $variations_structure );

			$atts['include'] = $product_id;

			// Fill-in missing attributes
			$r = wp_parse_args( $atts, Args::get_defaults() );

			// Return the table as HTML
			$output = apply_filters( 'wc_bulk_variations_product_output', wc_get_bulk_variations_table( $r, $atts ), $product_id );

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
						$product_id
					)
				)
			);

			printf(
				'<div class="%2$s" %3$s>%1$s</div>',
				$output,
				$classes,
				$styles
			);
		}
	}
}
