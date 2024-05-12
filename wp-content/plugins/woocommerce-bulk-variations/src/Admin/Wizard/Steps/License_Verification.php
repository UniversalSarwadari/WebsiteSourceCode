<?php
/**
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Bulk_Variations\Dependencies\Barn2\Setup_Wizard\Steps\Welcome;

class License_Verification extends Welcome {

	public function __construct() {
		parent::__construct();
		$this->set_name( esc_html__( 'Welcome', 'woocommerce-bulk-variations' ) );
		$this->set_title( esc_html__( 'Welcome to WooCommerce Bulk Variations', 'woocommerce-bulk-variations' ) );
		$this->set_description( esc_html__( 'Display product variations and edit them in bulk', 'woocommerce-bulk-variations' ) );
		$this->set_tooltip( esc_html__( 'Use this setup wizard to quickly configure the most popular options for your bulk variations grids. You can easily change these options later on the plugin settings page or by relaunching the setup wizard.', 'woocommerce-bulk-variations' ) );
	}

}
