<?php
/**
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Bulk_Variations\Dependencies\Barn2\Setup_Wizard\Api;
use Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
	Barn2\Plugin\WC_Bulk_Variations\Dependencies\Barn2\Setup_Wizard\Step,
	Barn2\Plugin\WC_Bulk_Variations\Dependencies\Barn2\Setup_Wizard\Util as Wizard_Util;

class General_Settings extends Step {

	/**
	 * Configure the step.
	 */
	public function __construct() {
		$this->set_id( 'general-settings' );
		$this->set_name( __( 'General', 'woocommerce-bulk-variations' ) );
		$this->set_description( __( 'Customize the bulk variations grid', 'woocommerce-bulk-variations' ) );
		$this->set_title( __( 'General settings', 'woocommerce-bulk-variations' ) );
		$this->set_tooltip( __( 'These options will affect all your variations grids. You can override them on the ‘Add/Edit Product’ screen for each individual product.', 'woocommerce-bulk-variations' ) );
		$this->set_hidden( true );
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {
		$settings = Settings::get_settings( $this->get_plugin() );
		$values   = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA );

		$fields = array_combine(
			[
				'enable',
				'enable_multivariation',
				'disable_purchasing',
				'show_stock',
				'hide_same_price',
			],
			Wizard_Util::pluck_wc_settings(
				$settings,
				[
					Settings::OPTION_VARIATIONS_DATA . '[enable]',
					Settings::OPTION_VARIATIONS_DATA . '[enable_multivariation]',
					Settings::OPTION_VARIATIONS_DATA . '[disable_purchasing]',
					Settings::OPTION_VARIATIONS_DATA . '[show_stock]',
					Settings::OPTION_VARIATIONS_DATA . '[hide_same_price]',
				]
			)
		);

		array_walk(
			$fields,
			function( &$v, $k ) use ( $values ) {
				$v['value']   = isset( $values[ $k ] ) ? $values[ $k ] : '';
				$v['classes'] = 'wcbvp-sw-main-checkbox';

				return $v;
			}
		);

		$fields['enable']['label']                = __( 'Products with 1 or 2 variation attributes', 'woocommerce-bulk-variations' );
		$fields['enable_multivariation']['label'] = __( 'Products with 3 or more variation attributes', 'woocommerce-bulk-variations' );
		$fields['disable_purchasing']['label']    = __( 'Disable purchasing', 'woocommerce-bulk-variations' );
		$fields['show_stock']['label']            = __( 'Show stock information', 'woocommerce-bulk-variations' );
		$fields['hide_same_price']['label']       = __( 'Hide same price', 'woocommerce-bulk-variations' );

		return $fields;
	}

	/**
	 * Update options in the database if needed.
	 *
	 * @return void
	 */
	public function submit( array $values ) {

		$settings = get_option( Settings::OPTION_VARIATIONS_DATA );
		$values   = array_map(
			function( $v ) {
				return filter_var( $v, FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';
			},
			wp_parse_args(
				$values,
				$settings
			)
		);

		$values['enable']                = filter_var( $values['enable'], FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';
		$values['enable_multivariation'] = filter_var( $values['enable_multivariation'], FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';
		$values['disable_purchasing']    = filter_var( $values['disable_purchasing'], FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';
		$values['show_stock']            = filter_var( $values['show_stock'], FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';
		$values['hide_same_price']       = filter_var( $values['hide_same_price'], FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';

		update_option( Settings::OPTION_VARIATIONS_DATA, $values );

		return Api::send_success_response();
	}

}
