<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Handlers;

use Barn2\Plugin\WC_Bulk_Variations\Util\Util,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service;

/**
 * This class handles the bulk add-to-cart.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Cart implements Registerable, Service {

	public function register() {
		add_action( 'template_redirect', [ $this, 'process_multi_cart' ], 20 );
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'add_to_cart_validation' ], 10, 3 );
	}

	public function process_multi_cart() {
		// Make sure we don't process form twice when adding via AJAX.
		if ( wp_is_json_request() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || defined( 'REST_REQUEST' ) || 'POST' !== Util::get_server_request_method() || ! isset( $_POST['quantity'] ) ) {
			return;
		}

		$quantities = $_POST['quantity'];

		if ( ! is_array( $quantities ) ) {
			return;
		}

		$quantities = array_filter( $quantities );

		if ( empty( $quantities ) ) {
			wc_add_notice( __( 'Please select one or more products.', 'woocommerce-bulk-variations' ), 'error' );

			return;
		}

		$this->add_to_cart_multi( $quantities );
	}

	public function get_product_variation_title( $product_variation_title, $product, $title_base, $title_suffix ) {
		return $title_suffix;
	}

	public function restore_variation_names( $variation_ids ) {
		// remove the filters so that they are not effective anywhere else
		remove_filter( 'woocommerce_product_variation_title_include_attributes', '__return_true' );
		remove_filter( 'woocommerce_product_variation_title', [ $this, 'get_product_variation_title' ], 10, 4 );

		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation ) {
				// once the filters are removed
				// it is enough to save each variation to restore the original name
				$variation->save();
			}
		}
	}

	/**
	 * Add multiple products to the cart in a single step.
	 *
	 * @param array $variations - An array of variations (including quantities and variation data) to add to the cart
	 * @return array An array of product IDs => quantity added
	 */
	public function add_to_cart_multi( $variations ) {
		$added_to_cart = [];

		if ( ! $variations ) {
			return $added_to_cart;
		}

		foreach ( $variations as $variation_id => $quantity ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation ) {
				$product_id = $variation->get_parent_id();

				if ( $this->add_to_cart( $product_id, $quantity, $variation_id ) ) {
					$added_to_cart[ $variation_id ] = $quantity;
				}
			}
		}

		if ( $added_to_cart ) {
			// add a custom message to the cart for all the added variations
			$this->add_cart_notice( $added_to_cart );

			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}

		// restore the variation names to their default value
		$this->restore_variation_names( array_keys( $variations ) );

		return $added_to_cart;
	}

	private function get_parent_title( $variation_ids ) {
		// all the variations belong to the same parent product
		// the first one is enough to get that
		$variation = wc_get_product( reset( $variation_ids ) );

		if ( ! $variation ) {
			// this should never happen!
			return '';
		}

		// get the parent product id
		$product_id = $variation->get_parent_id();

		return apply_filters( 'woocommerce_add_to_cart_item_name_in_quotes', sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), strip_tags( get_the_title( $product_id ) ) ), $product_id );
	}

	public function add_to_cart_validation( $validated, $product_id, $qty ) {
		// Bail if no product or invalid quantity
		$product        = wc_get_product( $product_id );
		$product_status = get_post_status( $product_id );

		if ( ! $product_id ) {
			wc_add_notice( __( 'No product selected. Please try again.', 'woocommerce-bulk-variations' ), 'error' );
			$validated = false;
		} elseif ( ! $product || ! $product->is_purchasable() ) {
			wc_add_notice( __( 'This product is no longer available. Please select an alternative.', 'woocommerce-bulk-variations' ), 'error' );
			$validated = false;
		} elseif ( ! $qty ) {
			wc_add_notice( __( 'Please enter a quantity greater than 0.', 'woocommerce-bulk-variations' ), 'error' );
			$validated = false;
		}

		return $validated;
	}

	public function add_cart_notice( $added_to_cart, $show_qty = false ) {
		$titles = [];

		// add the appropriate filters before getting the titles of each variation
		add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_true' );
		add_filter( 'woocommerce_product_variation_title', [ $this, 'get_product_variation_title' ], 10, 4 );

		foreach ( $added_to_cart as $variation_id => $qty ) {
			$variation = wc_get_product( $variation_id );

			// saving the variation triggers an update of the name
			// based on the added filters
			$variation->save();

			// compared to the default WooCommerce behavior ($qty > 1), show also quantities equal to 1 ($qty > 0)
			/* translators: %s: product name */
			$titles[] = apply_filters( 'woocommerce_add_to_cart_qty_html', ( $qty > 0 ? absint( $qty ) . ' &times; ' : '' ), $variation_id ) . strip_tags( get_the_title( $variation_id ) );
		}

		// sort the variation list based on the attribute names
		// to improve the readibility of the recap
		usort(
			$titles,
			function( $t1, $t2 ) {
				return strtolower( preg_replace( '/.*&times; /', '', $t1 ) ) > strtolower( preg_replace( '/.*&times; /', '', $t2 ) ) ? 1 : -1;
			}
		);

		$titles     = '<ul class="wcbvp-cart-variation-list"><li>' . implode( '</li><li>', array_filter( $titles ) ) . '</li></ul>';
		$added_text = sprintf(
			/* translators: 1: product name, 2: list of variations */
			__( '%1$s has been added to your cart in the following variations:%2$s', 'woocommerce-bulk-variations' ),
			apply_filters( 'wc_bulk_variation_cart_notice_product_title', $this->get_parent_title( array_keys( $added_to_cart ) ), $added_to_cart ),
			$titles
		);

		// Output success messages.
		if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			$return_to = apply_filters( 'woocommerce_continue_shopping_redirect', wc_get_raw_referer() ? wp_validate_redirect( wc_get_raw_referer(), false ) : wc_get_page_permalink( 'shop' ) );
			$message   = sprintf( '<a href="%s" tabindex="1" class="button wc-forward">%s</a> %s', esc_url( $return_to ), esc_html__( 'Continue shopping', 'woocommerce' ), wp_kses_post( $added_text ) );
		} else {
			$message = sprintf( '<a href="%s" tabindex="1" class="button wc-forward">%s</a> %s', esc_url( wc_get_cart_url() ), esc_html__( 'View cart', 'woocommerce' ), wp_kses_post( $added_text ) );
		}

		$message = apply_filters( 'wc_add_to_cart_message_html', $message, $added_to_cart, $show_qty );
		wc_add_notice( $message, apply_filters( 'woocommerce_add_to_cart_notice_type', 'success' ) );
	}

	public function add_to_cart( $product_id, $quantity = 1, $variation_id = false, $variations = [] ) {
		$variable_prod  = $variation_id ? wc_get_product( $variation_id ) : null;
		$qty            = wc_stock_amount( $quantity );

		$result = false;

		if ( $variable_prod ) {

			$attributes = $variable_prod->get_variation_attributes();

			foreach ( $attributes as $a_k => $a_v ) {

				if ( ! is_a( $a_v, 'WC_Product_Attribute' ) ) {

					$variations[ $a_k ] = $a_v;
				}
			}
		}

		if ( $variation_id ) {
			/**
			 * Filter the cart item data array so that other plugins can manipulate the cart item added by WBV
			 * 
			 * @since 2.0.6
			 * 
			 * @param array $cart_item_data An empty array passed to the filter callbacks
			 * @param int $product_id The ID of the variable product
			 * @param int|float $qty The quantity added to the cart
			 * @param int $variation_id The ID of the product variation
			 * @param array $variations An associative array with the variation attributes
			 */
			$cart_item_data = apply_filters( 'wc_bulk_variations_cart_item_data', [], $product_id, $qty, $variation_id, $variations );

			if ( apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $qty, $variation_id, $variations ) ) {
				if ( false !== WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variations, $cart_item_data  ) ) {
					$result = true;
				}
			}
		}

		return $result;
	}
}
