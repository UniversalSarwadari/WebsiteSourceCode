<?php

namespace WPO\WC\PDF_Invoices_Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\\WPO\\WC\\PDF_Invoices_Templates\\Dependencies' ) ) :

class Dependencies {

	public $php_version         = '5.3';
	public $woocommerce_version = '3.0';
	public $base_plugin_version = '3.7.3';
	
	protected static $_instance = null;
	
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Check if a certain plugin is installed on the website.
	 * 
	 * @param  string      $plugin        The plugin we're looking for.
	 * @param  bool        $partial_match whether or not to return partial matches
	 * @return array|bool                Representing the plugin data
	 */
	public function get_plugin( $plugin, $partial_match = false ){
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	
		$installed_plugins = get_plugins();
		// check for full matches first
		foreach ( $installed_plugins as $slug => $plugin_data ) {
			if ( $slug == $plugin && ! $this->is_plugin_deactivation_request( $slug ) ) {
				$plugin_data['partial_match'] = false;
				$plugin_data['slug']          = $slug;
				return $plugin_data;
			}
		}
	
		// check for partial match if enabled
		if ( $partial_match ) {
			foreach ( $installed_plugins as $slug => $plugin_data ) {
				if ( basename( $slug ) == basename( $plugin ) && ! $this->is_plugin_deactivation_request( $slug ) ) {
					$plugin_data['partial_match'] = true;
					$plugin_data['slug']          = $slug;
					return $plugin_data;
				}
			}
		}
	
		// no matches
		return false;
	}

	/**
	 * Checks if a plugin deactivation request is running
	 * 
	 * @param  string $slug  The plugin slug, eg. 'woocommerce/woocommerce.php'
	 * @return bool
	 */
	public function is_plugin_deactivation_request( $slug ) {
		if ( empty( $slug ) || ! isset( $_REQUEST['action'] ) || ! isset( $_REQUEST['plugin'] ) ) {
			return false;
		}

		if ( $_REQUEST['action'] == 'deactivate' && $_REQUEST['plugin'] == $slug ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the base plugin is ready to be loaded
	 * 
	 * @return boolean. Send notice(s) before returning.
	 */
	public function ready() {
		if ( version_compare( PHP_VERSION, $this->php_version, '<' ) ) {
			add_action( 'admin_notices', array ( $this, 'required_php_version' ) );
			return false;
		}

		if ( $this->is_woocommerce_activated() === false ) {
			add_action( 'admin_notices', array ( $this, 'need_woocommerce' ) );
			return false;
		}

		if ( version_compare( WC_VERSION, $this->woocommerce_version, '<' ) ) {
			add_action( 'admin_notices', array ( $this, 'update_woocommerce_notice' ) );
			return false;
		}

		$base_plugin = $this->get_plugin(
			'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php',
			true
		);
		
		if ( $base_plugin !== false ) {
			// plugin installed but version too low
			if ( class_exists('WPO_WCPDF') && version_compare( $base_plugin["Version"], $this->base_plugin_version, '<' ) ) {
				add_action( 'admin_notices', array ( $this, 'base_plugin_upgrade_requirement' ) );
				return false;
			} elseif ( ! class_exists('WPO_WCPDF') && ! class_exists('WooCommerce_PDF_Invoices') ) { 
				// plugin isn't active
				add_action( 'admin_notices', array ( $this, 'base_plugin_activate_requirement' ) );
				return false;
			} else { 
				// there's no issue
				return true;
			}
		} else { 
			// plugin isn't installed
			add_action( 'admin_notices', array ( $this, 'base_plugin_install_requirement' ) );
			return false;
		}
	}

	/**
	 * PHP version requirement notice
	 * 
	 * @return void
	 */
	public function required_php_version() {
		$error = sprintf( 
			/* translators: php version */
			__( 'PDF Invoices & Packing Slips for WooCommerce - Premium Templates requires PHP %s or higher.', 'wpo_wcpdf_templates' ), 
			$this->php_version
		);
		$how_to_update = __( 'How to update your PHP version', 'wpo_wcpdf_templates' );

		$message = sprintf( 
			'<div class="notice notice-error"><p>%1$s</p><p><a href="%2$s">%3$s</a></p></div>', 
			$error, 
			'https://docs.wpovernight.com/general/how-to-update-your-php-version/', 
			$how_to_update
		);

		echo $message;
	}

	/**
	 * Check if woocommerce is activated
	 * 
	 * @return bool
	 */
	public function is_woocommerce_activated() {
		$slug         = 'woocommerce/woocommerce.php';
		$fetch_plugin = $this->get_plugin( $slug, true );
		
		if ( $fetch_plugin !== false && function_exists( 'WC' ) ) { 
			return true;
		}

		return false; 
	}

	/**
	 * WooCommerce not active notice.
	 *
	 * @return void
	 */
	public function need_woocommerce() {
		$error = sprintf( 
			/* translators: <a> tags */
			__( 'PDF Invoices & Packing Slips for WooCommerce - Premium Templates requires %1$sWooCommerce%2$s to be installed & activated!' , 'wpo_wcpdf_templates' ), 
			'<a href="https://wordpress.org/plugins/woocommerce/">',
			'</a>'
		);
		$message = '<div class="notice notice-error"><p>' . $error . '</p></div>';
		echo $message;
	}

	/**
	 * WooCommerce not up-to-date notice.
	 *
	 * @return void
	 */
	public function update_woocommerce_notice() {
		$error   = sprintf(
			/* translators: 1: WooCommerce version, 2 & 3: <a> tags */
			__( 'PDF Invoices & Packing Slips for WooCommerce - Premium Templates requires at least version %1$s of WooCommerce to be installed. %2$sGet the latest version here%3$s!' , 'wpo_wcpdf_templates' ),
			$this->woocommerce_version,
			'<a href="https://wordpress.org/plugins/woocommerce/">',
			'</a>'
		);
		$message = '<div class="notice notice-error"><p>' . $error . '</p></div>';
		echo $message;
	}

	/**
	 * Base Plugin notice: not installed.
	 *
	 * @return void
	 */
	public function base_plugin_install_requirement() {
		$latest_version_url = 'https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/';

		$error = sprintf( 
			/* translators: 1: base plugin version, 2 & 3: <a> tags */
			__( 'PDF Invoices & Packing Slips for WooCommerce - Premium Templates requires at least version %1$s of PDF Invoices & Packing Slips for WooCommerce - %2$sget it here%3$s!' , 'wpo_wcpdf_templates' ),
				$this->base_plugin_version,
			'<a href="' . $latest_version_url . '" target="_blank" >',
			'</a>'  
		);
		$message = '<div class="notice notice-error"><p>' . $error . '</p></div>';
		echo $message;
	}
	
	/**
	 * Base plugin notice: installed but not activated.
	 *
	 * @return void
	 */
	public function base_plugin_activate_requirement() {
		$plugin_admin_url = esc_url_raw( network_admin_url( 'plugins.php?s=WooCommerce+PDF+Invoices' ) );

		$error = sprintf( 
			/* translators: <a> tags */
			__( 'PDF Invoices & Packing Slips for WooCommerce - Premium Templates requires the free base plugin to be activated! %1$sActivate it here!%2$s' , 'wpo_wcpdf_templates' ),
			'<a href="' . $plugin_admin_url . '" >',
			'</a>'
		);
		$message = '<div class="notice notice-error"><p>' . $error . '</p></div>';
		echo $message;
	}		

	/**
	* Base Plugin notice: below version 2.10.2+. 
	*
	* @return void
	*/
	public function base_plugin_upgrade_requirement() {
		$plugin_admin_url = esc_url_raw( network_admin_url( 'plugins.php?s=WooCommerce+PDF+Invoices' ) );

		$error = sprintf( 
			/* translators: 1: base plugin version, 2 & 3: <a> tags  */
			__( 'PDF Invoices & Packing Slips for WooCommerce - Premium Templates requires at least version %1$s of PDF Invoices & Packing Slips for WooCommerce. %2$sUpgrade to the latest version here%3$s!' , 'wpo_wcpdf_templates' ), 
			$this->base_plugin_version,
			'<a href="' . $plugin_admin_url . '" >',
			'</a>'
		);
		$message = '<div class="notice notice-error"><p>' . $error . '</p></div>';
		echo $message;
	}


	/**
	* WIP - this is not fully adapted to multisite and user permissions yet
	* Get a URL that will start the installation of a wordpress.org plugin
	*
	* @param  string $plugin_name  the wordpress.org plugin slug e.g. hello-dolly
	* @return string $url          URL to install a wordpress.org plugin directly
	*/
	public function get_plugin_install_url( $plugin_name ) {
		$action = 'install-plugin';
		$install_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => $action,
					'plugin' => $plugin_name
				),
				admin_url( 'update.php' )
			),
			$action.'_'.$plugin_name
		);

		return esc_url( $install_url );
	}		

	/**
	* WIP - this is not fully adapted to multisite and user permissions yet
	* Get a URL that will activate a plugin that's installed on the site
	*
	* @param  string $slug         the full local slug (file) e.g. hello-dolly/hello.php
	* @return string $activate_url URL to activate the plugin
	*/
	public function get_plugin_activate_url( $slug ) {
		$plugin_data = $this->get_plugin( $slug, true );
		$plugin_file = $plugin_data["slug"];

		$activate_url = add_query_arg( array(
			'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $plugin_file ),
			'action'   => 'activate',
			'plugin'   => $plugin_file,
		), network_admin_url( 'plugins.php' ) );

		if ( is_network_admin() ) {
			$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
		}

		return esc_url( $activate_url );
	}

	/**
	* WIP - this is not fully adapted to multisite and user permissions yet
	* Get a URL that will update a plugin that's installed on the site to the latest version
	*
	* @param  string $slug        the full local slug (file) e.g. hello-dolly/hello.php
	* @return string $upgrade_url URL to activate the plugin
	*/
	public function get_plugin_upgrade_url( $slug ) {
		$plugin_data = $this->get_plugin( $slug, true );
		$plugin_file = $plugin_data["slug"];

		$action = 'upgrade-plugin';
		$upgrade_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => $action,
					'plugin' => $plugin_file
				),
				admin_url( 'update.php' )
			),
			$action.'_'.$plugin_file
		);

		return esc_url( $upgrade_url );	
	} // end of temporary functions

} // end class

endif;
