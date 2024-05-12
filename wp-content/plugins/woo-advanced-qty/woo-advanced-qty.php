<?php
	/**
	 * Plugin Name:          WooCommerce Advanced Quantity
	 * Plugin URI:           https://morningtrain.dk
	 * Description:          Make the most out of your WooCommerce product quantity selection. This plugin allows you to make more specific product quantity incrementation.
	 * Version:              3.0.6
	 * Author:               Morning Train Technologies ApS
	 * Author URI:           https://morningtrain.dk
	 * License:              GPL-2.0+
	 * License URI:          https://www.gnu.org/licenses/gpl-2.0.txt
	 * Text Domain:          woo-advanced-qty
	 * Domain Path:          /languages
	 * Requires at least:    3.6.0
	 * Tested up to:         6.2
	 * WC requires at least: 3.2.0
	 * WC tested up to:      7.9
	 */

	// If this file is called directly, abort.
	if(!defined('WPINC')) die;

	require_once(__DIR__ . '/lib/class.plugin-init.php');
	\Morningtrain\WooAdvancedQTY\PluginInit::registerPlugin(__FILE__);