<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Integration;

use Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service;

/**
 * This class handles our bulk variations table on the product page.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Quick_View implements Registerable, Service {

	public function register() {
		// Integrate with WooCommerce Quick View Pro
		add_action( 'wc_quick_view_pro_before_quick_view', [ __CLASS__, 'integrate_quick_view' ], 9999 );
	}

	public static function integrate_quick_view() {
		global $post;
		if ( $post && $post->post_type === 'product' ) {

			$product_id = $post->ID;
			$product    = wc_get_product( $product_id );
			if ( $product && is_a( $product, 'WC_Product_Variable' ) ) {
				$attributes       = $product->get_variation_attributes();
				$attributes_count = count( $attributes );

				// check if grid is enabled
				$settings = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA, false );
				$enable   = $settings['enable'];

				if ( $attributes_count > 2 ) {
					// for 3+ attributes, check if grid is enabled
					$enable = $settings['enable_multivariation'];
				}

				$override = filter_var( get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA . '_override', true ), FILTER_VALIDATE_BOOLEAN );

				if ( $override ) {
					// if product-level control is active, check if grid is enabled
					$enable = filter_var( get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA . '_enable', true ), FILTER_VALIDATE_BOOLEAN );
				}

				if ( $enable ) {
					remove_action( 'wc_quick_view_pro_quick_view_product_details', 'woocommerce_template_single_price', 10 );
					remove_action( 'wc_quick_view_pro_quick_view_product_details', 'woocommerce_template_single_add_to_cart', 30 );
					add_action( 'wc_quick_view_pro_quick_view_product_details', [ 'Barn2\Plugin\WC_Bulk_Variations\Handlers\Variation_Table', 'print_variation_table' ], 9 );
					add_filter( 'wc_quick_view_pro_modal_container_class', [ __CLASS__, 'wc_quick_view_pro_modal_container_class' ] );
				} else {
					$variations_data = get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA, true );

					if ( isset( $variations_data['hide_add_to_cart'] ) && $variations_data['hide_add_to_cart'] ) {
						remove_action( 'wc_quick_view_pro_quick_view_product_details', 'woocommerce_template_single_add_to_cart', 30 );
					}
				}
			}
		}
	}

	public static function wc_quick_view_pro_modal_container_class( $classes ) {
		$classes[] = 'has-bulk-variations';

		return $classes;
	}
}
