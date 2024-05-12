<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Util;

use const Barn2\Plugin\WC_Bulk_Variations\PLUGIN_FILE;

/**
 * Utility functions for WooCommerce Bulk Variations.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Util {

	public static function get_db_version() {
		return get_option( 'wcbv_db_version', '1.0' );
	}

	public static function get_asset_url( $path = '' ) {
		return plugins_url( 'assets/' . ltrim( $path, '/' ), PLUGIN_FILE );
	}

	public static function get_wc_asset_url( $path = '' ) {
		if ( defined( 'WC_PLUGIN_FILE' ) ) {
			return plugins_url( 'assets/' . ltrim( $path, '/' ), WC_PLUGIN_FILE );
		}
		return false;
	}

	public static function sanitize_class_name( $class ) {
		return preg_replace( '/[^a-zA-Z0-9-_]/', '', $class );
	}

	public static function get_server_request_method() {
		return ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' );
	}

	public static function normalize_attribute_name( $slug ) {
		return str_replace( [ 'attribute_pa_', 'pa_' ], '', wc_sanitize_taxonomy_name( $slug ) );
	}

	public static function get_attribute_label( $name, $product = '' ) {

		global $wc_product_attributes;

		$original_label = wc_attribute_label( $name, $product );
		$label          = $original_label;

		$is_product_attribute = false;

		foreach ( $wc_product_attributes as $k_attribute => $v_attribute ) {

			$term = get_term_by( 'slug', $name, $k_attribute );
			if ( $term && $term instanceof \WP_Term ) {
				return $term->name;
			}
		}

		if ( ! $is_product_attribute ) {
			$label = str_replace( [ '-', '_' ], ' ', $label );
		}

		// perhaps this was a negative number, bring the sign back:
		if ( substr( $original_label, 0, 1 ) === '-' ) {
			$label = '-' . $label;
		}

		return $label;
	}

	public static function get_product_fields_for_filters() {
		$fields = [
			'_regular_price'    => [
				'label'       => __( 'Regular price', 'woocommerce-bulk-variations' ),
				'type'        => 'number',
				'placeholder' => '0.00',
			],
			'_sale_price'       => [
				'label'       => __( 'Sale price', 'woocommerce-bulk-variations' ),
				'type'        => 'number',
				'placeholder' => '0.00',
			],
			'_enabled'          => [
				'label' => __( 'Enabled', 'woocommerce-bulk-variations' ),
				'type'  => 'toggle',
			],
			'_downloadable'     => [
				'label' => __( 'Downloadable', 'woocommerce-bulk-variations' ),
				'type'  => 'toggle',
			],
			'_virtual'          => [
				'label' => __( 'Virtual', 'woocommerce-bulk-variations' ),
				'type'  => 'toggle',
			],
			'_manage_stock'     => [
				'label' => __( 'Manage stock', 'woocommerce-bulk-variations' ),
				'type'  => 'toggle',
			],
			'_stock_status'     => [
				'label' => __( 'Stock status', 'woocommerce-bulk-variations' ),
				'type'  => 'stock',
			],
			'_stock'            => [
				'label'       => __( 'Stock', 'woocommerce-bulk-variations' ),
				'type'        => 'number',
				'placeholder' => 'quantity (e.g. 15)',
			],
			'_low_stock_amount' => [
				'label'       => __( 'Low stock threshold', 'woocommerce-bulk-variations' ),
				'type'        => 'number',
				'placeholder' => 'quantity (e.g. 15)',
			],
			'_sale_price_dates' => [
				'label'       => __( 'Sale schedule', 'woocommerce-bulk-variations' ),
				'type'        => 'date',
				'placeholder' => 'YYYY-MM-DD',
			],
			'_sku'              => [
				'label'       => __( 'SKU', 'woocommerce-bulk-variations' ),
				'type'        => 'text',
				'placeholder' => 'SKU',
			],
		];

		return apply_filters( 'wc_bulk_variations_product_fields_for_filters', $fields );
	}

	public static function get_compare_operators_for_filters() {
		return apply_filters(
			'wc_bulk_variations_compare_operators_for_filters',
			[
				// toggle compare
				[
					'value' => '=',
					'label' => __( '=', 'woocommerce-bulk-variations' ),
					'type'  => 'number',
				],
				[
					'value' => '!=',
					'label' => __( '!=', 'woocommerce-bulk-variations' ),
					'type'  => 'number',
				],
				[
					'value' => '>',
					'label' => __( '>', 'woocommerce-bulk-variations' ),
					'type'  => 'number',
				],
				[
					'value' => '>=',
					'label' => __( '>=', 'woocommerce-bulk-variations' ),
					'type'  => 'number',
				],
				[
					'value' => '<',
					'label' => __( '<', 'woocommerce-bulk-variations' ),
					'type'  => 'number',
				],
				[
					'value' => '<=',
					'label' => __( '<=', 'woocommerce-bulk-variations' ),
					'type'  => 'number',
				],

				// toggle compare
				[
					'value'  => 'on',
					'label'  => __( 'Yes', 'woocommerce-bulk-variations' ),
					'type'   => 'toggle',
					'values' => 0,
				],
				[
					'value'  => 'off',
					'label'  => __( 'No', 'woocommerce-bulk-variations' ),
					'type'   => 'toggle',
					'values' => 0,
				],

				// text compare
				[
					'value' => 'LIKE',
					'label' => __( 'contains', 'woocommerce-bulk-variations' ),
					'type'  => 'text',
				],
				[
					'value' => 'NOT LIKE',
					'label' => __( 'doesn\'t contain', 'woocommerce-bulk-variations' ),
					'type'  => 'text',
				],

				// stock status compare
				[
					'value'  => 'IN',
					'label'  => __( 'is one of', 'woocommerce-bulk-variations' ),
					'type'   => 'stock',
					'values' => 0,
				],
				[
					'value'  => 'NOT IN',
					'label'  => __( 'is not one of', 'woocommerce-bulk-variations' ),
					'type'   => 'stock',
					'values' => 0,
				],

				// date compare
				[
					'value' => 'starts_before',
					'label' => __( 'starts before', 'woocommerce-bulk-variations' ),
					'type'  => 'date',
				],
				[
					'value' => 'ends_before',
					'label' => __( 'ends before', 'woocommerce-bulk-variations' ),
					'type'  => 'date',
				],
				[
					'value' => 'starts_after',
					'label' => __( 'starts after', 'woocommerce-bulk-variations' ),
					'type'  => 'date',
				],
				[
					'value' => 'ends_after',
					'label' => __( 'ends after', 'woocommerce-bulk-variations' ),
					'type'  => 'date',
				],
				[
					'value'  => 'running',
					'label'  => __( 'is running', 'woocommerce-bulk-variations' ),
					'type'   => 'date',
					'values' => 0,
				],
				[
					'value'  => 'not_running',
					'label'  => __( 'is not running', 'woocommerce-bulk-variations' ),
					'type'   => 'date',
					'values' => 0,
				],
				[
					'value'  => 'BETWEEN',
					'label'  => __( 'is between', 'woocommerce-bulk-variations' ),
					'type'   => 'number,date',
					'double' => true,
					'values' => 2,
				],
				[
					'value'  => 'NOT BETWEEN',
					'label'  => __( 'is not between', 'woocommerce-bulk-variations' ),
					'type'   => 'number',
					'double' => true,
					'values' => 2,
				],
			]
		);
	}

}
