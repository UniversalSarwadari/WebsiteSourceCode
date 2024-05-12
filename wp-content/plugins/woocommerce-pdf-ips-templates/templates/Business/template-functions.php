<?php
/**
 * Use this file for all your template filters and actions.
 * Requires PDF Invoices & Packing Slips for WooCommerce 1.4.13 or higher
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'wpo_wcpdf_template_editor_defaults', 'wpo_wcpdf_business_template_defaults', 9, 3 );
add_filter( 'wpo_wcpdf_template_editor_settings', 'wpo_wcpdf_business_template_defaults', 9, 3 );
function wpo_wcpdf_business_template_defaults ( $settings, $template_type, $settings_name ) {
	$editor_settings = get_option('wpo_wcpdf_editor_settings');

	if (isset($editor_settings['settings_saved']) && !isset($_GET['load-defaults'])) {
		return $settings;
	}

	// only packing slip is different
	if ( $template_type == 'packing-slip' ) {
		switch ($settings_name) {
			case 'columns':
				$settings = array (
					1 => array (
						'type'			=> 'sku',
					),
					2 => array (
						'type'			=> 'description',
						'show_meta'		=> 1,
					),
					3 => array (
						'type'			=> 'quantity',
					),
				);
				break;
			case 'totals':
				$settings = array();
				break;
		}
	} else {
		switch ($settings_name) {
			case 'columns':
				$settings = array (
					1 => array (
						'type'			=> 'sku',
					),
					2 => array (
						'type'			=> 'description',
						'show_meta'		=> 1,
					),
					3 => array (
						'type'			=> 'quantity',
					),
					4 => array (
						'type'			=> 'price',
						'price_type'	=> 'single',
						'tax'			=> 'excl',
						'discount'		=> 'before',
					),
					5 => array (
						'type'			=> 'tax_rate',
					),
					6 => array (
						'type'			=> 'price',
						'price_type'	=> 'total',
						'tax'			=> 'excl',
						'discount'		=> 'before',
					),
				);
				break;
			case 'totals':
				$settings = array(
					1 => array (
						'type'			=> 'subtotal',
						'tax'			=> 'excl',
						'discount'		=> 'before',
					),
					2 => array (
						'type'			=> 'discount',
						'tax'			=> 'excl',
					),
					3 => array (
						'type'			=> 'shipping',
						'tax'			=> 'excl',
					),
					4 => array (
						'type'			=> 'fees',
						'tax'			=> 'excl',
					),
					5 => array (
						'type'			=> 'vat',
					),
					6 => array (
						'type'			=> 'total',
						'tax'			=> 'incl',
					),
				);
				break;
		}
	}

	return $settings;
}
