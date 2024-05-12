<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Admin;

use Barn2\Plugin\WC_Bulk_Variations\Util\Util,
	Barn2\WBV_Lib\Plugin\Licensed_Plugin,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service,
	Barn2\WBV_Lib\Service_Container,
	Barn2\WBV_Lib\Plugin\Admin\Admin_Links,
	Barn2\WBV_Lib\WooCommerce\Admin\Navigation;

/**
 * General admin functions for WooCommerce Private Store.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Admin_Controller implements Registerable, Service {

	use Service_Container;

	private $plugin;

	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function get_services() {
		$services = [
			'admin_links'   => new Admin_Links( $this->plugin ),
			'navigation'    => new Navigation( $this->plugin, 'wc-bulk-variations', __( 'Bulk Variations', 'woocommerce-bulk-variations' ) ),
			'settings_page' => new Settings_Page( $this->plugin ),
		];

		if ( $this->plugin->get_license()->is_valid() ) {
			$services['updater']           = new Updater();
			$services['products_page']     = new Products_Page( $this->plugin );
			$services['variation_manager'] = new Variation_Manager( $this->plugin );
			// $services['customize_term']    = new Customize_Term( $this->plugin );
		}

		return $services;
	}

	public function register() {
		$this->register_services();

		// Load admin scripts.
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
	}

	public function load_scripts( $hook ) {
		if ( 'edit-tags.php' === $hook ) {
			wp_enqueue_style( 'wc-bulk-variations-admin', plugins_url( 'assets/css/admin/wc-bulk-variations-admin.min.css', $this->plugin->get_file() ), [], $this->plugin->get_version() );
		}

		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wc-bulk-variations-settings', plugins_url( 'assets/css/admin/wc-bulk-variations-settings.min.css', $this->plugin->get_file() ), [], $this->plugin->get_version() );
		wp_enqueue_script( 'wc-bulk-variations-admin', Util::get_asset_url( 'js/admin/wc-bulk-variations-admin.min.js' ), [ 'jquery' ], $this->plugin->get_version(), true );
	}
}
