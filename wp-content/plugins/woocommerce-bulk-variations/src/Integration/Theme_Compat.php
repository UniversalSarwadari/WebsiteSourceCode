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
class Theme_Compat implements Registerable, Service {

	public function __construct() {
		$this->theme = strtolower( get_template() );
	}

	public function register() {
		// Add compatibility with shopkeeper theme
		add_action( 'woocommerce_single_product_summary_single_add_to_cart', [ $this, 'shopkeeper_compat' ] );
	}

	public function get_theme_vars() {
		switch ( $this->theme ) {
			case 'storefront':
				$vars = [
					get_theme_mod( 'storefront_button_text_color' ),
					get_theme_mod( 'storefront_button_background_color' ),
					get_theme_mod( 'background_color' ),
					get_theme_mod( 'storefront_accent_color' ),
					'30px',
				];
				break;

			case 'astra':
				$astra_color_palettes = get_option( 'astra-color-palettes' );
				$current_palette      = $astra_color_palettes['currentPalette'];
				$current_palette      = $astra_color_palettes['palettes'][ $current_palette ];
				$astra_settings       = get_option( 'astra-settings' );
				$radius               = "{$astra_settings['button-radius']}px";

				$vars = [
					$current_palette[3],
					$current_palette[6],
					$current_palette[5],
					$current_palette[0],
					$radius,
				];
				break;

			case 'avada':
				$fusion_options = get_option( 'fusion_options' );

				$vars = [
					$fusion_options['body_typography']['color'],
					$fusion_options['bg_color'],
					'#fff',
					$fusion_options['primary_color'],
					'30px',
				];
				break;

			case 'divi':
				$hl_bg  = \ET_Global_Settings::get_value( 'link_color' );
				$color  = \ET_Global_Settings::get_value( 'font_color' );
				$radius = \ET_Global_Settings::get_value( 'all_buttons_border_radius' );

				$vars = [
					$color,
					'#eee',
					'#fff',
					$hl_bg,
					"{$radius}px",
				];
				break;

			default:
				$vars = [
					'#6d6d6d',
					'#eee',
					'#fff',
					'#058',
					'30px',
				];
		}

		return array_combine(
			[
				'--color',
				'--background-color',
				'--highlight-color',
				'--highlight-background-color',
				'--radius',
			],
			$vars
		);
	}

	public function get_theme_vars_styles() {
		$styles = $this->get_theme_vars();

		$styles = implode(
			';',
			array_map(
				function( $k, $v ) {
					return "$k:$v";
				},
				array_keys( $styles ),
				$styles
			)
		);

		return "style=\"$styles\"";
	}

	public function get_theme_classes() {
		switch ( $this->theme ) {
			case 'storefront':
				$classes = [];
				break;

			case 'astra':
				$classes = [];
				break;

			case 'avada':
				$classes = [ 'table-2' ];
				break;

			default:
				$classes = [];
		}

		return $classes;
	}

	public function shopkeeper_compat() {

		global $post;
		if ( $post && $post->post_type === 'product' ) {

			$product_id  = $post->ID;
			$product_obj = wc_get_product( $product_id );

			$settings = get_option( Settings::OPTION_VARIATIONS_DATA, false );
			$override = ( isset( $settings['enable'] ) && $settings['enable'] === 'yes' ) ? $settings['enable'] : false;

			if ( metadata_exists( 'post', $product_id, Settings::OPTION_VARIATIONS_DATA . '_enable' ) ) {
				$override = get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA . '_enable', true );
			}

			if ( $override ) {
				$attributes_count = 0;

				if ( $product_obj instanceof \WC_Product_Variable ) {
					$attributes       = $product_obj->get_variation_attributes();
					$attributes_count = count( $attributes );
				}

				if ( $attributes_count && $attributes_count <= 2 ) {
					// Add compatibility with shopkeeper theme
					remove_action( 'woocommerce_single_product_summary_single_add_to_cart', 'woocommerce_template_single_add_to_cart', 30 );
					remove_action( 'woocommerce_single_product_summary_single_meta', 'woocommerce_template_single_meta', 40 );
					remove_action( 'woocommerce_single_product_summary_single_sharing', 'woocommerce_template_single_sharing', 50 );
				}
			} else {
				$variations_data = get_post_meta( $product_id, Settings::OPTION_VARIATIONS_DATA, true );

				if ( isset( $variations_data['hide_add_to_cart'] ) && $variations_data['hide_add_to_cart'] ) {
					// Add compatibility with shopkeeper theme
					remove_action( 'woocommerce_single_product_summary_single_add_to_cart', 'woocommerce_template_single_add_to_cart', 30 );
					remove_action( 'woocommerce_single_product_summary_single_meta', 'woocommerce_template_single_meta', 40 );
					remove_action( 'woocommerce_single_product_summary_single_sharing', 'woocommerce_template_single_sharing', 50 );
				}
			}
		}
	}
}
