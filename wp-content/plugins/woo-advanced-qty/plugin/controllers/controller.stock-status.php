<?php

namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class StockStatusController extends Controller {

	protected function registerActions() {
		parent::registerFilters();

		Loader::addAction('woocommerce_before_product_object_save', static::class, 'setStockStatusBeforeSaveAction', 10, 1);
	}

	/**
	 * Since WooCommerce Version 7.8.0 the stock status is set based on a stock quantity castet to int - we do the same but casting to float instead
	 *
	 * @param $product
	 *
	 * @return void
	 */
	public static function setStockStatusBeforeSaveAction($product) {
		// No need to do anything if not managed stock
		if(!$product->get_manage_stock()) {
			return;
		}

		// No need to use resources if product is variable, since it will be handled by the variations
		if($product->is_type('variable')) {
			return;
		}

		$stock_is_above_notification_threshold = (float) $product->get_stock_quantity() > absint( get_option( 'woocommerce_notify_no_stock_amount', 0 ) );
		$backorders_are_allowed = ( 'no' !== $product->get_backorders() );

		if ( $stock_is_above_notification_threshold ) {
			$new_stock_status = 'instock';
		} elseif ( $backorders_are_allowed ) {
			$new_stock_status = 'onbackorder';
		} else {
			$new_stock_status = 'outofstock';
		}

		$product->set_stock_status( $new_stock_status );
	}
}