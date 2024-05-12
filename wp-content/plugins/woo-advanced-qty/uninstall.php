<?php
	/**
	 * Fired during plugin uninstallation.
	 *
	 * @link       https://morningtrain.dk
	 * @since      2.4.0
	 *
	 * @package    Woo_Advanced_QTY
	 */

	if(!defined('WP_UNINSTALL_PLUGIN')) {
		exit;
	}

	/**
	 * Fired during plugin uninstallation.
	 *
	 * This class defines all code necessary to run during the plugin's uninstallation.
	 *
	 * @since      2.4.0
	 * @package    Woo_Advanced_QTY
	 * @author     André Winther Olsen <ao@morningtrain.dk>
	 */

	//Check user capability
	if(!current_user_can('activate_plugins')) {
		return;
	}

	//Check if it's this plugin being deleted
	if($plugin != WP_UNINSTALL_PLUGIN) {
		return;
	}

	//Setting names
	$options = array(
		'_advanced-qty-min',
		'_advanced-qty-max',
		'_advanced-qty-step',
		'_advanced-qty-step-intervals',
		'_advanced-qty-value',
		'_advanced-qty-price-suffix',
		'_advanced-qty-quantity-suffix',
		'_advanced-qty-input-picker',
		'_advanced-qty-individually-variation',
		'product-category-advanced-qty-min',
		'product-category-advanced-qty-step',
		'product-category-advanced-qty-max',
		'product-category-advanced-qty-step-intervals',
		'product-category-advanced-qty-value',
		'product-category-advanced-qty-price-suffix',
		'product-category-advanced-qty-quantity-suffix',
		'product-category-advanced-qty-input-picker',
		'woo-advanced-qty-min',
		'woo-advanced-qty-max',
		'woo-advanced-qty-step',
		'woo-advanced-qty-step-intervals-unformated',
		'woo-advanced-qty-value',
		'woo-advanced-qty-price-suffix',
		'woo-advanced-qty-quantity-suffix',
		'woo-advanced-qty-input-picker',
		'woo-advanced-qty-show-price-suffix-on-cart-page',
		'woo-advanced-qty-show-quantity-suffix-on-cart-page',
		'woo-advanced-qty-show-the-same-input-type-on-cart-page',
		'woo-advanced-qty-triggers-auto-cart-refresh',
		'woo-advanced-qty-hide-update-button',
		'woo-advanced-qty-slider-number-format'
	);

	//Clean plugin options from database
	foreach($options as $option) {
		//Delete post options
		if(substr($option, 0, 1) === '_') {
			delete_post_meta_by_key($option);
		}

		//Delete global/category options
		if(get_option($option)) {
			delete_option($option);
		}

		if(is_multisite()) {
			delete_site_option($option);
		}
	}


