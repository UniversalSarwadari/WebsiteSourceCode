<?php
/**
 * The main plugin file for WooCommerce Bulk Variations.
 *
 * This file is included during the WordPress bootstrap process if the plugin is active.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 *
 * @wordpress-plugin
 * Plugin Name:     WooCommerce Bulk Variations
 * Plugin URI:      https://barn2.com/wordpress-plugins/woocommerce-bulk-variations/
 * Description:     Displays product variations in a grid layout or price matrix.
 * Version:         2.2.0
 * Author:          Barn2 Plugins
 * Author URI:      https://barn2.com
 * Text Domain:     woocommerce-bulk-variations
 * Domain Path:     /languages
 *
 * WC requires at least: 3.7
 * WC tested up to: 7.4.1
 *
 * Copyright:       Barn2 Plugins Ltd
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Barn2\Plugin\WC_Bulk_Variations;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PLUGIN_FILE    = __FILE__;
const PLUGIN_VERSION = '2.2.0';

require_once __DIR__ . '/vendor/autoload.php';

if ( ! function_exists( 'wbv' ) ) {
	/**
	 * Helper function to return the main plugin instance.
	 *
	 * @return Plugin
	 */
	function wbv() {
		return Plugin_Factory::create( PLUGIN_FILE, PLUGIN_VERSION );
	}
}

// Load the plugin.
wbv()->register();
