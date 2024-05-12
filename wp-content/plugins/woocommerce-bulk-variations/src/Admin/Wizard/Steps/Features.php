<?php
/**
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Bulk_Variations\Dependencies\Barn2\Setup_Wizard\Api;
use Barn2\Plugin\WC_Bulk_Variations\Dependencies\Barn2\Setup_Wizard\Step;

class Features extends Step {

	/**
	 * Configure the step.
	 */
	public function __construct() {
		$this->set_id( 'features' );
		$this->set_name( __( 'Features', 'woocommerce-bulk-variations' ) );
		$this->set_title( __( 'Managing and displaying variations', 'woocommerce-bulk-variations' ) );
		$this->set_description( __( 'Which bulk variations features do you plan to use?', 'woocommerce-bulk-variations' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {
		return [
			'backend'  => [
				'type'    => 'checkbox',
				'label'   => __( 'Add and edit variations in bulk', 'woocommerce-bulk-variations' ),
				'value'   => get_option( 'wcbvp_wizard_use_backend' ) === 'yes',
				'classes' => [ 'wcbvp-sw-main-checkbox' ],
			],
			'frontend' => [
				'type'    => 'checkbox',
				'label'   => __( 'Display variations in a grid layout', 'woocommerce-bulk-variations' ),
				'value'   => get_option( 'wcbvp_wizard_use_frontend' ) === 'yes',
				'classes' => [ 'wcbvp-sw-main-checkbox' ],
			],
		];
	}

	/**
	 * Update options in the database if needed.
	 *
	 * @return void
	 */
	public function submit( array $values ) {

		$fronted = filter_var( $values['frontend'], FILTER_VALIDATE_BOOLEAN );
		$backend = filter_var( $values['backend'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $fronted && ! $backend ) {
			return Api::send_error_response(
				[
					'message' => __( 'Please select at least one option' )
				]
			);
		}

		if ( $fronted ) {
			update_option( 'wcbvp_wizard_use_frontend', 'yes' );
		} else {
			delete_option( 'wcbvp_wizard_use_frontend' );
		}

		if ( $backend ) {
			update_option( 'wcbvp_wizard_use_backend', 'yes' );
		} else {
			delete_option( 'wcbvp_wizard_use_backend' );
		}

		return Api::send_success_response();
	}

}
