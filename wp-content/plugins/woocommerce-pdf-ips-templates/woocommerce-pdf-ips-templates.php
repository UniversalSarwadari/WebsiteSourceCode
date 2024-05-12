<?php
/**
 * Plugin Name:          PDF Invoices & Packing Slips for WooCommerce - Premium Templates
 * Plugin URI:           https://wpovernight.com/downloads/woocommerce-pdf-invoices-packing-slips-premium-templates/
 * Description:          Premium templates for the PDF Invoices & Packing Slips for WooCommerce extension
 * Version:              2.21.2
 * Author:               WP Overnight
 * Author URI:           https://wpovernight.com/
 * License:              GPLv2 or later
 * License URI:          https://opensource.org/licenses/gpl-license.php
 * Text Domain:          wpo_wcpdf_templates
 * WC requires at least: 3.0
 * WC tested up to:      8.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WPO_WCPDF_Templates' ) ) :

class WPO_WCPDF_Templates {

	public $version = '2.21.2';
	public $plugin_basename;
	public $third_party_plugins;
	public $settings;
	public $main;
	public $dependencies;
	public $updater;

	protected static $_instance = null;

	/**
	 * Main Plugin Instance
	 *
	 * Ensures only one instance of plugin is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
			self::$_instance->autoloaders();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_basename = plugin_basename( __FILE__ );

		$this->define( 'WPO_WCPDF_TEMPLATES_VERSION', $this->version );

		// load the localisation & classes
		add_action( 'plugins_loaded', array( $this, 'translations' ) );
		add_action( 'wpo_wcpdf_reload_attachment_translations', array( $this, 'translations' ) );
		add_action( 'wpo_wcpdf_reload_text_domains', array( $this, 'translations' ) );

		add_action( 'init', array( $this, 'load_classes' ), 9 );

		// Load the updater
		add_action( 'init', array( $this, 'load_updater' ), 0 );

		// run lifecycle methods
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'wp_loaded', array( $this, 'do_install' ) );
		}

		// Add premium templates to settings page listing
		add_filter( 'wpo_wcpdf_template_paths', array( $this, 'register_template_path' ), 1, 1 );
		
		// HPOS compatibility
		add_action( 'before_woocommerce_init', array( $this, 'woocommerce_hpos_compatible' ) );

		// On activation
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// On deactivation
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}
	
	private function autoloaders() {
		// main plugin autoloader
		require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Load the translation / textdomain files
	 * 
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 */
	public function translations() {
		if ( function_exists( 'determine_locale' ) ) { // WP5.0+
			$locale = determine_locale();
		} else {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		}
		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-pdf-invoices-packing-slips' );
		$dir    = trailingslashit( WP_LANG_DIR );

		/**
		 * Frontend/global Locale. Looks in:
		 *
		 * 		- WP_LANG_DIR/woocommerce-pdf-ips-templates/wpo_wcpdf_templates-LOCALE.mo
		 * 	 	- WP_LANG_DIR/plugins/wpo_wcpdf_templates-LOCALE.mo
		 * 	 	- woocommerce-pdf-ips-templates/languages/wpo_wcpdf_templates-LOCALE.mo (which if not found falls back to:)
		 * 	 	- WP_LANG_DIR/plugins/wpo_wcpdf_templates-LOCALE.mo
		 */
		if ( in_array( current_filter(), array( 'wpo_wcpdf_reload_attachment_translations', 'wpo_wcpdf_reload_text_domains' ) ) ) {
			unload_textdomain( 'wpo_wcpdf_templates' );
		}
		load_textdomain( 'wpo_wcpdf_templates', $dir . 'woocommerce-pdf-ips-templates/wpo_wcpdf_templates-' . $locale . '.mo' );
		load_textdomain( 'wpo_wcpdf_templates', $dir . 'plugins/wpo_wcpdf_templates-' . $locale . '.mo' );
		load_plugin_textdomain( 'wpo_wcpdf_templates', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
	}

	/**
	 * Load the main plugin classes and functions
	 */
	public function includes() {
		// plugin functions
		include_once( $this->plugin_path() . '/includes/wcpdf-templates-functions.php' );
		
		$this->third_party_plugins = WPO\WC\PDF_Invoices_Templates\Compatibility\Third_Party_Plugins::instance();
		$this->settings            = WPO\WC\PDF_Invoices_Templates\Settings::instance();
		$this->main                = WPO\WC\PDF_Invoices_Templates\Main::instance();

		// backwards compatibility
		$GLOBALS['wpo_wcpdf_templates'] = WPO\WC\PDF_Invoices_Templates\Legacy\Templates::instance();
	}

	/**
	 * Instantiate classes when woocommerce is activated
	 */
	public function load_classes() {
		$this->dependencies = $this->load_dependencies();

		if ( false === $this->dependencies->ready() ) {
			return;
		}

		// all systems ready - GO!
		$this->includes();
	}

	/**
	 * Add premium templates to settings page listing
	 */
	public function register_template_path( $template_paths ) {
		$template_paths['premium_plugin'] = $this->plugin_path() . '/templates/';
		return $template_paths;
	}
	

	/** Lifecycle methods *******************************************************
	 * Because register_activation_hook only runs when the plugin is manually
	 * activated by the user, we're checking the current version against the
	 * version stored in the database
	****************************************************************************/


	/**
	 * Handles version checking
	 */
	public function do_install() {
		$this->dependencies = $this->load_dependencies();
		
		if ( false === $this->dependencies->ready() ) {
			return;
		}

		$version_setting   = 'wpo_wcpdf_templates_version';
		$installed_version = get_option( $version_setting );

		// installed version lower than plugin version?
		if ( version_compare( $installed_version, WPO_WCPDF_TEMPLATES_VERSION, '<' ) ) {

			if ( ! $installed_version ) {
				$this->install();
			} else {
				$this->upgrade( $installed_version );
			}

			// new version number
			update_option( $version_setting, WPO_WCPDF_TEMPLATES_VERSION );
		}
	}

	/**
	 * Plugin install method. Perform any installation tasks here
	 */
	protected function install() {
		$option = 'wpo_wcpdf_settings_general';
		// switch to Simple Premium when installing for the first time
		if ( $settings = get_option( $option, array() ) ) {
			$settings['template_path'] = 'premium_plugin/Simple Premium';
			update_option( $option, $settings );
		}
	}

	/**
	 * Plugin upgrade method. Perform any required upgrades here
	 *
	 * @param string $installed_version the currently installed ('old') version
	 */
	protected function upgrade( $installed_version ) {
		// 2.1.5 Upgrade: set default footer height for Simple Premium (2cm)
		if ( version_compare( $installed_version, '2.1.5', '<' ) ) {
			$template_settings = get_option('wpo_wcpdf_template_settings');
			if (isset($template_settings['template_path']) && strpos($template_settings['template_path'],'Simple Premium') !== false ) {
				$template_settings['footer_height'] = '2cm';
				update_option( 'wpo_wcpdf_template_settings', $template_settings );
			}
		}

		// 2.1.7 Upgrade: set show meta as default in product block
		if ( version_compare( $installed_version, '2.1.7', '<' ) ) {
			$editor_settings = get_option('wpo_wcpdf_editor_settings');
			$documents = array('invoice','packing-slip','proforma','credit-note');
			foreach ($documents as $document) {
				if (isset($editor_settings['fields_'.$document.'_columns'])) {
					foreach ($editor_settings['fields_'.$document.'_columns'] as $key => $column) {

						if (isset($column['type']) && $column['type'] == 'description') {
							$column['show_meta'] = 1;
						}
						$editor_settings['fields_'.$document.'_columns'][$key] = $column;
					}
				}
			}
			update_option('wpo_wcpdf_editor_settings', $editor_settings);
		}

		// 2.4.0 Upgrade: footer height moved to General settings
		if ( version_compare( $installed_version, '2.4.0', '<' ) ) {
			// load legacy settings
			$template_settings = get_option('wpo_wcpdf_template_settings');
			if (!empty($template_settings['footer_height'])) {
				// copy footer height to new general settings option
				$general_settings = get_option('wpo_wcpdf_settings_general');
				$general_settings['footer_height'] = $template_settings['footer_height'];
				update_option( 'wpo_wcpdf_settings_general', $general_settings );
			}
		}
		
		// 2.20.3 Upgrade: reassign editor columns/totals keys
		if ( version_compare( $installed_version, '2.20.3-dev-1', '<' ) ) {
			$option          = 'wpo_wcpdf_editor_settings';
			$editor_settings = get_option( $option, [] ); // get latest settings
			$documents       = WPO_WCPDF()->documents->get_documents();
			$update          = false;
				
			foreach ( $documents as $document ) {
				$keys = [
					"fields_{$document->get_type()}_columns",
					"fields_{$document->get_type()}_totals",
				];
				
				foreach ( $keys as $key ) {
					if ( isset( $editor_settings[$key] ) ) {
						$editor_settings[$key] = array_combine( range( 1, count( $editor_settings[$key] ) ), array_values( $editor_settings[$key] ) );
						$update                = true;
					}
				}
			}
			
			if ( $update ) {
				update_option( $option, $editor_settings );	
			}
		}
	}

	/**
	 * Activation hook
	 * 
	 * Get transient for template when activating the plugin
	 */
	public function activate() {
		if( $template_path = get_transient( 'wpo_wcpdf_premium_template_selected' ) ) {
			$general_settings                  = get_option( 'wpo_wcpdf_settings_general' );
			$general_settings['template_path'] = $template_path;
			update_option( 'wpo_wcpdf_settings_general', $general_settings );
		}
	}

	/**
	 * Deactivation hook
	 * 
	 * Set transient for template when deactivating the plugin
	 */
	public function deactivate() {
		$general_settings = get_option( 'wpo_wcpdf_settings_general' );
		if( ! empty( $general_settings['template_path'] ) ) {
			set_transient( 'wpo_wcpdf_premium_template_selected', $general_settings['template_path'], MONTH_IN_SECONDS );

			// set template option to empty to use Simple
			$general_settings['template_path'] = '';
			update_option( 'wpo_wcpdf_settings_general', $general_settings );
		}
	}

	/**
	 * Run the updater scripts from the WPO Sidekick
	 * @return void
	 */
	public function load_updater() {
		// Init updater data
		$item_name    = 'PDF Invoices & Packing Slips for WooCommerce - Premium Templates';
		$file         = __FILE__;
		$license_slug = 'wpo_wcpdf_templates_license';
		$version      = $this->version;
		$author       = 'WP Overnight';

		// load updater
		if ( class_exists( 'WPO_Updater' ) ) { // WP Overnight Sidekick plugin
			$this->updater = new WPO_Updater( $item_name, $file, $license_slug, $version, $author );
		} else { // bundled updater
			if ( ! class_exists( 'WPO_Update_Helper' ) ) {
				include_once( $this->plugin_path() . '/updater/update-helper.php' );
			}
			$this->updater = new WPO_Update_Helper( $item_name, $file, $license_slug, $version, $author );
		}

		// if license not activated, show notice in plugin settings page
		if ( is_callable( array( $this->updater, 'license_is_active' ) ) && ! $this->updater->license_is_active() ) {
			add_action( 'wpo_wcpdf_before_settings_page', array( $this, 'no_active_license_message' ), 1 );
		}

	}
	
	private function load_dependencies() {
		if ( empty( $this->dependencies ) ) {
			$this->dependencies = WPO\WC\PDF_Invoices_Templates\Dependencies::instance();
		}
		
		return $this->dependencies;
	}

	/**
	 * Displays message if the license is not activated
	 * 
	 * @return void
	 */
	public function no_active_license_message( $active_tab )
	{
		if( class_exists( 'WPO_Updater' ) ) {
			$activation_url = esc_url_raw( network_admin_url( 'admin.php?page=wpo-license-page' ) );
		} else {
			$activation_url = esc_url_raw( network_admin_url( 'plugins.php?s=Premium+Templates#woocommerce-pdf-ips-templates-manage-license' ) );
		}
		?>
		<div class="notice notice-warning inline">
			<p>
				<?php
					printf(
						/* translators: 1. plugin name, 2. click here */
						__( 'Your license of %1$s has not been activated on this site, %2$s to enter your license key.', 'wpo_wcpdf_templates' ),
						'<strong>'.__( 'PDF Invoices & Packing Slips for WooCommerce - Premium Templates', 'wpo_wcpdf_templates' ).'</strong>',
						'<a href="'.$activation_url.'">'.__( 'click here', 'wpo_wcpdf_templates' ).'</a>'
					);
				?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Declares WooCommerce HPOS compatibility.
	 *
	 * @return void
	 */
	public function woocommerce_hpos_compatible() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

} // class WPO_WCPDF_Templates

endif; // class_exists

/**
 * Returns the main instance of PDF Invoices & Packing Slips for WooCommerce to prevent the need to use globals.
 *
 * @since  1.6
 * @return WPO_WCPDF_Templates
 */
function WPO_WCPDF_Templates() {
	return WPO_WCPDF_Templates::instance();
}

WPO_WCPDF_Templates(); // load plugin
