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

class Images extends Step {

	/**
	 * Configure the step.
	 */
	public function __construct() {
		$this->set_id( 'images' );
		$this->set_name( __( 'Images', 'woocommerce-bulk-variations' ) );
		$this->set_description( __( 'Choose how to display the image of each variation in the grid', 'woocommerce-bulk-variations' ) );
		$this->set_title( __( 'Variation images', 'woocommerce-bulk-variations' ) );
		$this->set_hidden( true );
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {
		$settings    = Settings::get_settings( $this->get_plugin() );
		$data_values = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA );

		$fields = array_combine(
			[
				'variation_images',
				'add_images_to_gallery',
				'use_lightbox',
			],
			Wizard_Util::pluck_wc_settings(
				$settings,
				[
					Settings::OPTION_VARIATIONS_DATA . '[variation_images]',
					Settings::OPTION_VARIATIONS_DATA . '[add_images_to_gallery]',
					Settings::OPTION_VARIATIONS_DATA . '[use_lightbox]',
				]
			)
		);

		$fields['variation_images']['classes'] = [ 'wcbvp-sw-select' ];
		$fields['variation_images']['value']   = array_values(
			array_filter(
				$fields['variation_images']['options'],
				function( $option ) use ( $data_values ) {
					return $option['value'] === $data_values['variation_images'];
				}
			)
		);

		$fields['add_images_to_gallery']['value']       = filter_var( $data_values['add_images_to_gallery'], FILTER_VALIDATE_BOOLEAN );
		$fields['add_images_to_gallery']['label']       = $fields['add_images_to_gallery']['description'];
		$fields['add_images_to_gallery']['description'] = '';
		$fields['add_images_to_gallery']['conditions']  = [
			'variation_images' => [
				'op'    => 'neq',
				'value' => 'off',
			],
		];

		$fields['use_lightbox']['value']       = filter_var( $data_values['use_lightbox'], FILTER_VALIDATE_BOOLEAN );
		$fields['use_lightbox']['label']       = $fields['use_lightbox']['description'];
		$fields['use_lightbox']['description'] = '';
		$fields['use_lightbox']['conditions']  = [
			'variation_images' => [
				'op'    => 'neq',
				'value' => 'off',
			],
		];

		return $fields;
	}

	/**
	 * Update options in the database if needed.
	 *
	 * @return void
	 */
	public function submit( array $values ) {
		$settings = get_option( Settings::OPTION_VARIATIONS_DATA );
		$fields   = $this->get_fields();

		if ( is_array( $values['variation_images'] ) ) {
			$values['variation_images'] = $values['variation_images'];
		}

		$values['use_lightbox'] = filter_var( $values['use_lightbox'], FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no';

		$values = wp_parse_args(
			array_intersect_key( $values, [ 'variation_images', 'add_images_to_gallery', 'use_lightbox' ] ),
			$settings
		);

		update_option( Settings::OPTION_VARIATIONS_DATA, $values );

		return Api::send_success_response();
	}

}
