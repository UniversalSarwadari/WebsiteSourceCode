<?php
/**
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard;

use Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps\License_Verification;
use Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps\Features;
use Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps\General_Settings;
use Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps\Images;
use Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps\Upsell;
use Barn2\Plugin\WC_Bulk_Variations\Admin\Wizard\Steps\Completed;
use Barn2\Plugin\WC_Bulk_Variations\Dependencies\Barn2\Setup_Wizard\Setup_Wizard as Wizard;
use Barn2\Plugin\WC_Bulk_Variations\Util\Settings;
use Barn2\WBV_Lib\Plugin\License\EDD_Licensing;
use Barn2\WBV_Lib\Plugin\License\Plugin_License;
use Barn2\WBV_Lib\Plugin\Licensed_Plugin;
use Barn2\WBV_Lib\Registerable;
use Barn2\WBV_Lib\Util as Lib_Util;

class Setup_Wizard implements Registerable {

	private $plugin;

	private $wizard;

	public function __construct( Licensed_Plugin $plugin ) {

		$this->plugin = $plugin;

		$steps = [
			new License_Verification(),
			new Features(),
			new General_Settings(),
			new Images(),
			new Upsell(),
			new Completed(),
		];

		$wizard = new Wizard( $this->plugin, $steps );

		$wizard->configure(
			[
				'skip_url'        => admin_url( 'admin.php?page=wc-settings&tab=products&section=bulk-variations' ),
				'license_tooltip' => esc_html__( 'The licence key is contained in your order confirmation email.', 'woocommerce-bulk-variations' ),
				'utm_id'          => 'wbv',
				'signpost'        => [
					[
						'title' => __( 'Products', 'woocommerce-bulk-variations' ),
						'href'  => add_query_arg( 'post_type', 'product', admin_url( 'edit.php' ) ),
					],
					[
						'title' => __( 'Go to settings page', 'woocommerce-bulk-variations' ),
						'href'  => admin_url( 'admin.php?page=wc-settings&tab=products&section=bulk-variations' ),
					],
				],
			]
		);

		$wizard->add_edd_api( EDD_Licensing::class );
		$wizard->add_license_class( Plugin_License::class );
		$wizard->add_restart_link( Settings::SECTION_SLUG, 'bulk_variations_pro_settings_header' );

		$wizard->add_custom_asset(
			$plugin->get_dir_url() . 'assets/js/admin/wizard.min.js',
			Lib_Util::get_script_dependencies( $this->plugin, 'admin/wizard.min.js' )
		);

		$this->wizard = $wizard;

	}

	public function register() {
		$this->wizard->boot();

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_additional_scripts' ], 21 );
	}

	public function enqueue_additional_scripts( $hook_suffix ) {
		if ( 'toplevel_page_' . $this->wizard->get_slug() !== $hook_suffix ) {
			return;
		}

		$all_products_url = admin_url( 'edit.php?post_type=product' );
		wp_add_inline_script( $this->wizard->get_slug(), 'const all_products_url = ' . wp_json_encode( $all_products_url ) . ';' );
		wp_enqueue_style( 'wcbvp-setup-wizard-addons', $this->plugin->get_dir_url() . 'assets/css/admin/wizard.min.css', [ $this->wizard->get_slug() ], $this->plugin->get_version() );
	}

}
