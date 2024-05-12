<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Integration;

use Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service;

/**
 * This class handles the integration with WooCommerce Fast Cart.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Fast_Cart implements Registerable, Service {

	public function register() {
		add_action( 'wfc_script_params', [ $this, 'wfc_script_params' ] );
	}

	public function wfc_script_params( $params ) {
		$params['selectors']['cartBtn'] = '.wc-bulk-variations-table-wrapper form button.single_add_to_cart_button';

		return $params;
	}
}
