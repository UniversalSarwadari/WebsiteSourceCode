<?php
/**
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Bulk_Variations\Dependencies\Barn2\Setup_Wizard\Steps\Ready;

class Completed extends Ready {

	public function __construct() {
		parent::__construct();
		$this->set_name( esc_html__( 'Ready', 'woocommerce-bulk-variations' ) );
		$this->set_title( esc_html__( 'Setup Complete', 'woocommerce-bulk-variations' ) );
		$this->set_description( esc_html__( 'Congratulations, you have finished setting up the plugin! If you want to manage variations in bulk or configure the variations grid separately for individual products, then you can do this on the ‘Edit Product’ screen.', 'woocommerce-bulk-variations' ) );
	}

}
