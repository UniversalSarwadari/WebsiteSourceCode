<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Handlers;

use Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
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
class Images implements Registerable, Service {

	public function register() {
		add_filter( 'woocommerce_product_get_image_id', [ $this, 'get_image_id' ], 10, 2 );
		add_filter( 'woocommerce_product_get_gallery_image_ids', [ $this, 'get_gallery_image_ids' ], 10, 2 );
	}

	/**
	 * Filter the image ID of a product so that the first variation image is returned if no product image is set
	 *
	 * @param  int $image_id
	 * @param  WC_Product $product
	 * @return int
	 */
	public function get_image_id( $image_id, $product ) {
		$settings   = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA );

		if ( is_a( $product, 'WC_Product_Variable' ) && empty( $image_id ) ) {
			$variations = $product->get_children();

			if ( empty( $variations ) ) {
				return $image_id;
			}

			$variation = wc_get_product( $variations[0] );

			if ( $variation_image_id = $variation->get_image_id() ) {
				$image_id = $variation_image_id;
			}
		}

		return $image_id;
	}

	/**
	 * Filter the gallery image IDs so that the variation images can be added to it
	 *
	 * @param  array $gallery_image_ids
	 * @param  WC_Product $product
	 * @return array
	 */
	public function get_gallery_image_ids( $gallery_image_ids, $product ) {
		$settings   = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA );

		/**
		 * Filter whether to add variation images to the gallery
		 *
		 * @since 2.2.0
		 * @param bool $add_images
		 * @param WC_Product $product
		 */
		$add_images = apply_filters( 'wc_bulk_variations_add_images_to_gallery', $settings['add_images_to_gallery'] ?? false, $product );

		if ( is_a( $product, 'WC_Product_Variable' ) && $add_images ) {
			$variations = $product->get_children();

			foreach ( $variations as $variation ) {
				$variation = wc_get_product( $variation );

				if ( $variation_image_id = $variation->get_image_id() ) {
					$gallery_image_ids[] = $variation_image_id;
				}
			}

			$gallery_image_ids = array_unique( $gallery_image_ids );
		}

		if ( $image_id = $product->get_image_id() ) {
			$gallery_image_ids = array_diff( $gallery_image_ids, [ $image_id ] );
		}

		return $gallery_image_ids;
	}
}