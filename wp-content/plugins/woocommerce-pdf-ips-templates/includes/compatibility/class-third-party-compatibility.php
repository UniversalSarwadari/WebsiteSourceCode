<?php

namespace WPO\WC\PDF_Invoices_Templates\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\\WPO\\WC\\PDF_Invoices_Templates\\Compatibility\\Third_Party_Plugins' ) ) :

class Third_Party_Plugins {
	
	protected static $_instance = null;
	
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function __construct() {
		add_filter( 'wpo_wcpdf_item_tax_rate_base', array( $this, 'germanized_split_shipping_taxes_tax_base' ), 10, 4 );
		add_action( 'wpo_wcpdf_before_html', array( $this, 'woodmart_lazy_loading' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_html', array( $this, 'woodmart_lazy_loading' ), 10, 2 );
		add_filter( 'wpo_wcpdf_order_items_data', array( $this, 'restore_product_bundle_grouping' ), 999, 3 );
	}

	/**
	 * WooCommerce Germanized allows calculating multiple tax rates for shipping,
	 * splitting the vat base (taxable amount) accross the different rates
	 * to make our vat base/subtotal rows match this, we use their custom meta to
	 * get the taxable amount
	 * 
	 * @param float          $base_amount the vat base as normally calculated
	 * @param \WC_Order_item $item        the order item
	 * @param object         $tax         Standard object holding the tax data
	 * @param \WC_Order      $order       the WooCommerce order.
	 * @return float
	 */
	public function germanized_split_shipping_taxes_tax_base( $base_amount, $item, $tax, $order ) {
		if ( ! function_exists( 'WC_germanized' ) ) {
			return $base_amount;
		}
		if ( is_a( $item, 'WC_Order_Item_Shipping') && $split_taxes = $item->get_meta( '_split_taxes' ) ) {
			if ( is_array( $split_taxes ) ) {
				foreach ($split_taxes as $tax_class => $split_tax ) {
					if ( ! empty( $split_tax['net_amount'] ) && ! empty( $split_tax['tax_rates'] ) && in_array( $tax->rate_id, $split_tax['tax_rates'] ) ) {
						return $split_tax['net_amount'];
					}
				}
			}
		}
		return $base_amount;
	}

	/**
	 * Enables/disables the Woodmart theme lazy loading
	 * 
	 * @param string $document_type document type
	 * @param object $document      document object
	 * 
	 * @return void
	 */
	public function woodmart_lazy_loading( $document_type, $document ) {
		$current_action = current_action();
		if ( $current_action == 'wpo_wcpdf_before_html' && function_exists( 'woodmart_lazy_loading_deinit' ) ) {
			woodmart_lazy_loading_deinit( true );
		} elseif ( $current_action == 'wpo_wcpdf_after_html' && function_exists( 'woodmart_lazy_loading_init' ) ) {
			woodmart_lazy_loading_init( true );
		}
	}

	/**
	 * When WooCommerce Product Bundles is activated, keep bundles
	 * together if a sorting option is selected within the customizer.
	 *
	 * @param array  $items         The document items
	 * @param object $order         WC_Order
	 * @param array  $document_type The document type
	 *
	 * @return array The order items filtered
	 */
	public function restore_product_bundle_grouping( $items, $order, $document_type ) {
		if ( function_exists( 'wc_pb_get_bundled_order_items' ) && function_exists( 'wc_pb_get_bundled_order_item_container' ) ) {
			// insert bundled items under their parent
			$reordered_items = array();
			foreach ( $items as $item_id => $item ) {
				// skip items that we have already moved
				if ( isset( $reordered_items[$item_id] ) ) {
					continue;
				}
				// check if item is a bundle
				if ( $bundled_item_ids = wc_pb_get_bundled_order_items( $item['item'], $order, true ) ) {
					// add bundle container
					$reordered_items[$item_id] = $item;
					// add bundled items below parent
					$reordered_items = $reordered_items + array_intersect_key( $items, array_flip( $bundled_item_ids ) );
				} elseif ( false === wc_pb_get_bundled_order_item_container( $item['item'], $order ) ) { // regular item
					$reordered_items[$item_id] = $item;
				}
			}
			
			return $reordered_items;
		}
		return $items;
	}

} // end class

endif;
