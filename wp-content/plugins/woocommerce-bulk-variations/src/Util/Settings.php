<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Util;

use Barn2\Plugin\WC_Bulk_Variations\Args,
	Barn2\WBV_Lib\Util as Lib_Util;

/**
 * Utility functions for the product table plugin settings.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Settings {
	/* Option names for our plugin settings (i.e. the option keys used in wp_options) */

	const OPTION_VARIATIONS_DATA      = 'wcbvp_variations_data';
	const OPTION_VARIATIONS_STRUCTURE = 'wcbvp_variations_structure';

	/* The section name within the main WooCommerce Settings */
	const SECTION_SLUG = 'bulk-variations';

	/**
	 * Get the default values of the variations data options
	 *
	 * @return array
	 */
	public static function get_setting_variations_defaults() {
		return self::get_setting( self::OPTION_VARIATIONS_DATA, [] );
	}

	/**
	 * Convert boolean arguments into `yes`/`no` values
	 *
	 * @param  array $args The array with the original arguments
	 * @return array
	 */
	public static function bulk_args_to_settings( $args ) {
		if ( empty( $args ) ) {
			return $args;
		}

		foreach ( $args as $key => $value ) {
			if ( is_bool( $value ) ) {
				$args[ $key ] = $value ? 'yes' : 'no';
			}
		}

		return $args;
	}

	public static function get_setting( $option_name, $default = [] ) {
		$option_value = get_option( $option_name, $default );

		if ( is_array( $option_value ) ) {
			// Merge with defaults.
			if ( is_array( $default ) ) {
				$option_value = wp_parse_args( $option_value, $default );
			}

			// Convert 'yes'/'no' options to booleans.
			$option_value = array_map( [ __CLASS__, 'array_map_yes_no_to_boolean' ], $option_value );
		}

		return $option_value;
	}

	private static function array_map_yes_no_to_boolean( $val ) {
		if ( 'yes' === $val || 1 === $val ) {
			return true;
		} elseif ( 'no' === $val || 0 === $val ) {
			return false;
		}

		return $val;
	}

	public static function get_global_attributes( $choose_label ) {
		global $wc_product_attributes;

		$options = [
			'' => $choose_label,
		];

		// Array of defined attribute taxonomies.
		$attribute_taxonomies = wc_get_attribute_taxonomies();

		if ( ! empty( $attribute_taxonomies ) ) {
			foreach ( $attribute_taxonomies as $tax ) {
				$attribute_taxonomy_name             = wc_attribute_taxonomy_name( $tax->attribute_name );
				$label                               = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
				$options[ $attribute_taxonomy_name ] = $label;
			}
		}

		return $options;
	}

	public static function get_settings( $plugin ) {
		$default_args       = self::bulk_args_to_settings( Args::$default_args );
		$documentation_url  = $plugin->get_documentation_url();
		$documentation_link = "<a target='_blank' href='$documentation_url'>" . __( 'Documentation', 'woocommerce-bulk-variations' ) . '</a>';

		$settings = [
			[
				'id'    => 'bulk_variations_pro_settings_start',
				'type'  => 'settings_start',
				'class' => 'barn2-plugins-settings'
			],
			[
				'title' => __( 'Bulk variations', 'woocommerce-bulk-variations' ),
				'type'  => 'title',
				'id'    => 'bulk_variations_pro_settings_header',
				'desc'  => '<p>' . __( 'The following options control the WooCommerce Bulk Variations extension.', 'woocommerce-bulk-variations' ) . '<p>'
				. '<p>'
				. $documentation_link . ' | '
				. Lib_Util::barn2_link( 'support-center/', __( 'Support', 'woocommerce-bulk-variations' ), true )
				. '</p>'
			],
			$plugin->get_license_setting()->get_license_key_setting(),
			$plugin->get_license_setting()->get_license_override_setting(),
			[
				'type' => 'sectionend',
				'id'   => 'bulk_variations_pro_settings_license',
			],
			[
				'title' => '',
				'type'  => 'title',
				'id'    => 'bulk_variations_pro_settings_title',
			],
			[
				'title'         => __( 'Grid options', 'woocommerce-bulk-variations' ),
				'type'          => 'checkbox',
				'id'            => self::OPTION_VARIATIONS_DATA . '[enable]',
				'desc'          => __( 'Use the variations grid for all products with 1 or 2 variation attributes', 'woocommerce-bulk-variations' ),
				'default'       => $default_args['enable'],
				'checkboxgroup' => 'start',
			],
			[
				'type'          => 'checkbox',
				'id'            => self::OPTION_VARIATIONS_DATA . '[enable_multivariation]',
				'desc'          => __( 'Use the variations grid for all products with 3 or more variation attributes', 'woocommerce-bulk-variations' ),
				'default'       => $default_args['enable_multivariation'],
				'checkboxgroup' => '',
			],
			[
				'type'          => 'checkbox',
				'id'            => self::OPTION_VARIATIONS_DATA . '[disable_purchasing]',
				'desc'          => __( 'Display the variations grid without quantity boxes or add to cart button', 'woocommerce-bulk-variations' ),
				'default'       => $default_args['disable_purchasing'],
				'checkboxgroup' => '',
			],
			[
				'type'          => 'checkbox',
				'id'            => self::OPTION_VARIATIONS_DATA . '[show_stock]',
				'desc'          => __( 'Display stock information in the variations grid', 'woocommerce-bulk-variations' ),
				'default'       => $default_args['show_stock'],
				'checkboxgroup' => '',
			],
			[
				'type'          => 'checkbox',
				'id'            => self::OPTION_VARIATIONS_DATA . '[show_description]',
				'desc'          => __( 'Display description in the variations grid', 'woocommerce-bulk-variations' ),
				'default'       => $default_args['show_description'],
				'checkboxgroup' => '',
			],
			[
				'type'          => 'checkbox',
				'id'            => self::OPTION_VARIATIONS_DATA . '[hide_same_price]',
				'desc'          => __( 'Hide prices if all variations have the same price', 'woocommerce-bulk-variations' ),
				'default'       => $default_args['hide_same_price'],
				'checkboxgroup' => 'end',
			],
			[
				'title'    => __( 'Variation images', 'woocommerce-bulk-variations' ),
				'type'     => 'select',
				'id'       => self::OPTION_VARIATIONS_DATA . '[variation_images]',
				'options'  => [
					'off' => __( 'Do not show any images', 'woocommerce-bulk-variations' ),
					'col' => __( 'Show horizontally', 'woocommerce-bulk-variations' ),
					'row' => __( 'Show vertically', 'woocommerce-bulk-variations' ),
				],
				'desc_tip' => __( 'Whether the images should be displayed horizontally or vertically.', 'woocommerce-bulk-variations' ),
				'default'  => $default_args['variation_images'],
			],
			[
				'type'     => 'checkbox',
				'id'       => self::OPTION_VARIATIONS_DATA . '[add_images_to_gallery]',
				'desc'     => __( 'Show variation images in product gallery', 'woocommerce-bulk-variations' ),
				'desc_tip' => __( 'Automatically include variation images in the product gallery. Recommended image size: 1,000 x 1,000px or larger.', 'woocommerce-bulk-variations' ),
				'default'  => $default_args['add_images_to_gallery'],
			],
			[
				'type'    => 'checkbox',
				'id'      => self::OPTION_VARIATIONS_DATA . '[use_lightbox]',
				'desc'    => __( 'Open variation images in a lightbox', 'woocommerce-bulk-variations' ),
				'default' => $default_args['use_lightbox'],
			],
			[
				'title'    => __( 'Default attributes', 'woocommerce-bulk-variations' ),
				'type'     => 'select',
				'id'       => self::OPTION_VARIATIONS_STRUCTURE . '[columns]',
				'options'  => self::get_global_attributes( __( 'Use the first attribute of each product', 'woocommerce-bulk-variations' ) ),
				'desc_tip' => __( 'Select which global attributes should be used by default as the horizontal and vertical headers of the grid.', 'woocommerce-bulk-variations' ),
				'desc'     => __( 'Default horizontal attribute.', 'woocommerce-bulk-variations' ),
				'default'  => $default_args['columns'],
				'class'    => 'wcbvp-attribute-selector',
			],
			[
				'type'    => 'select',
				'id'      => self::OPTION_VARIATIONS_STRUCTURE . '[rows]',
				'options' => self::get_global_attributes( __( 'Use the second attribute of each product', 'woocommerce-bulk-variations' ) ),
				'desc'    => __( 'Default vertical attribute.', 'woocommerce-bulk-variations' ),
				'default' => $default_args['rows'],
				'class'   => 'wcbvp-attribute-selector',
			],
			[
				'title'    => __( 'Single variation attribute', 'woocommerce-bulk-variations' ),
				'type'     => 'select',
				'id'       => self::OPTION_VARIATIONS_DATA . '[variation_attribute]',
				'options'  => [
					''     => __( 'Display horizontally', 'woocommerce-bulk-variations' ),
					'vert' => __( 'Display vertically', 'woocommerce-bulk-variations' ),
				],
				'desc_tip' => __( 'Grid layout for products with only one variation attribute.', 'woocommerce-bulk-variations' ),
				'default'  => $default_args['variation_attribute'],
			],
			[
				'type' => 'sectionend',
				'id'   => 'bulk_variations_pro_settings_content',
			],
			[
				'id'   => 'bulk_variations_pro_settings_end',
				'type' => 'settings_end',
			]
		];

		$section_id = self::SECTION_SLUG;

		return apply_filters( "woocommerce_get_settings_{$section_id}", $settings );
	}
}
