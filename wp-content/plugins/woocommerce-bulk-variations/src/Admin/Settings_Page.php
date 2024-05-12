<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Admin;

use Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Plugin\Licensed_Plugin,
	Barn2\WBV_Lib\WooCommerce\Admin\Custom_Settings_Fields,
	Barn2\WBV_Lib\WooCommerce\Admin\Plugin_Promo;

/**
 * Provides functions for the plugin settings page in the WordPress admin.
 *
 * Settings can be accessed at WooCommerce -> Settings -> Products -> Bulk variations.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Settings_Page implements Registerable {

	const SHORTCODE_DEFAULTS_SECTION_ID = 'bulk_variations_pro_shortcode_defaults';

	private $plugin;

	public function __construct( Licensed_Plugin $plugin ) {
		$this->id              = Settings::SECTION_SLUG;
		$this->label           = __( 'Bulk Variations', 'woocommerce-bulk-variations' );
		$this->plugin          = $plugin;
		$this->license_setting = $plugin->get_license_setting();
	}

	public function register() {
		// Register our custom settings types.
		$extra_setting_fields = new Custom_Settings_Fields( $this->plugin );
		$extra_setting_fields->register();

		// Add sections & settings
		add_filter( 'woocommerce_get_sections_products', [ $this, 'add_section' ] );
		add_filter( 'woocommerce_get_settings_products', [ $this, 'add_settings' ], 10, 2 );

		// Add plugin promo.
		( new Plugin_Promo( $this->plugin, 'products', $this->id ) )->register();
	}

	public function add_section( $sections ) {
		$sections[ Settings::SECTION_SLUG ] = __( 'Bulk variations', 'woocommerce-bulk-variations' );
		return $sections;
	}

	public function add_settings( $settings, $current_section ) {

		if ( $this->id !== $current_section ) {
			return $settings;
		}

		return Settings::get_settings( $this->plugin );
	}

}
