<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Data;

use Barn2\Plugin\WC_Bulk_Variations\Util\Util;

use function Barn2\Plugin\WC_Discontinued_Products\wdp;

/**
 * Factory class to get the product table data object for a given column.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Data_Factory {

	/**
	 * The full list of table args.
	 *
	 * @var Args
	 */
	private $args;

	/**
	 * The main class constructor
	 *
	 * @param array $args The array with all configuration options.
	 */
	public function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * Retrieve the default quantity for each product
	 *
	 * @param WC_Product $product The product
	 * @return int The default quantity
	 */
	public function get_default_quantity( $product ) {

		$default = 0;

		if ( $product->is_sold_individually() ) {
			$default = 0;
		}

		$default = apply_filters_deprecated( 'woocommerce_default_quantity_value', [ $default, $product ], '2.0.0', 'wc_bulk_variations_default_quantity_value' );

		return apply_filters( 'wc_bulk_variations_default_quantity_value', $default, $product );
	}

	/**
	 * Generate the HTML markup of an image
	 *
	 * @param  string $variation_attribute The attribute to retrieve an image for
	 * @return string The HTML markup of the retrieved image or empty string
	 */
	public function get_attribute_image_html( $variation_attribute, $aria_label ) {
		if ( 'off' === $this->args->variation_images ) {
			return false;
		}

		$use_lightbox = $this->args->use_lightbox;
		$image_props  = $this->get_variation_image_id( $variation_attribute );
		$image_id     = $image_props['image_id'];
		$product_id   = $image_props['product_id'];

		if ( $image_id &&
			$use_lightbox &&
			false === has_action( 'wp_footer', 'woocommerce_photoswipe' ) &&
			false === has_action( 'wp_footer', [ 'Barn2\Plugin\WC_Bulk_Variations\Frontend_Scripts', 'load_photoswipe_template' ] ) ) {

			wp_enqueue_style( 'photoswipe-default-skin' );
			wp_enqueue_script( 'photoswipe-ui-default' );
			add_action( 'wp_footer', [ 'Barn2\Plugin\WC_Bulk_Variations\Frontend_Scripts', 'load_photoswipe_template' ] );
		}

		return $this->get_image_html( $image_id, $aria_label, $use_lightbox, $product_id );
	}

	public function get_attribute_term_image( $term_slug, $dimension = 'rows' ) {
		$dimension_attributes = $this->get_dimension_attributes();

		if ( ! $dimension_attributes || ! isset( $dimension_attributes[ $dimension ] ) ) {
			return false;
		}

		$dimension_attribute = 'pa_' . Util::normalize_attribute_name( $dimension_attributes[ $dimension ] );

		if ( isset( $this->args->term_thumbnails[ $dimension_attribute ] ) &&
			isset( $this->args->term_thumbnails[ $dimension_attribute ][ $term_slug ] ) ) {

			$image_id = $this->args->term_thumbnails[ $dimension_attribute ][ $term_slug ];

			if ( wp_attachment_is_image( $image_id ) ) {
				return $image_id;
			}
		}

		return false;
	}

	public function get_variation_image_id( $attribute ) {
		$variation_id = 0;
		$image_id     = 0;

		$ids = $this->args->get_attribute_variation_ids( $attribute );

		foreach ( $ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation ) {
				// use an empty context so that the parent image is not returned
				$image_id = $variation->get_image_id( '' );
			}

			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				return [
					'image_id'   => $image_id,
					'product_id' => $variation_id,
				];
			}
		}

		// no variation image was found
		// use the first variation to get the parent image
		$variation_id = reset( $ids );
		$variation    = wc_get_product( $variation_id );

		if ( $variation ) {
			// the parent image is automatically returned when context is `view`
			$image_id = $variation->get_image_id( 'view' );

			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				return [
					'image_id'   => $image_id,
					'product_id' => $variation->get_parent_id(),
				];
			}
		}

		return false;
	}

	public function get_image_html( $image_id, $aria_label = '', $use_lightbox = true, $product_id = 0 ) {
		$attachment_image_src = wp_get_attachment_image_src( $image_id, 'full' );
		$attachment_thumb_src = wp_get_attachment_image_src( $image_id, 'thumbnail' );

		if ( isset( $attachment_image_src[0] ) ) {

			$image_atts = [
				'title'                   => $aria_label,
				'alt'                     => $aria_label,
				'data-caption'            => get_post_field( 'post_excerpt', $image_id ),
				'data-src'                => $attachment_image_src[0],
				'data-large_image'        => $attachment_image_src[0],
				'data-large_image_width'  => $attachment_image_src[1],
				'data-large_image_height' => $attachment_image_src[2],
				'class'                   => 'product-thumbnail product-table-image',
				'style'                   => 'max-width:64px; max-height: 64px;',
				'aria-label'              => $aria_label,
				'data-variation'          => $product_id,
			];

			// Alt fallbacks
			$image_atts['alt'] = empty( $image_atts['alt'] ) ? $image_atts['data-caption'] : $image_atts['alt'];
			$image_atts['alt'] = empty( $image_atts['alt'] ) ? $image_atts['title'] : $image_atts['alt'];

			$data = '';

			if ( $use_lightbox ) {
				$data .= sprintf(
					'<div data-thumb="%1$s" class="product-thumbnail-wrapper woocommerce-product-gallery__image"><a href="%2$s">',
					esc_url( $attachment_thumb_src[0] ),
					esc_url( $attachment_image_src[0] )
				);
			} else {
				$data .= '<div class="wcbvp-image-container">';
			}

			$data .= wp_get_attachment_image( $image_id, [ 64, 64, true ], false, $image_atts );

			if ( $use_lightbox ) {
				$data .= '</a></div>';
			} else {
				$data .= '</div>';
			}

			return $data;
		}

		return false;
	}

	public function get_cell_content( $column, $row ) {
		$variation_ids = $this->args->get_cell_variation_ids( $column, $row );
		$variation_id  = reset( $variation_ids );
		$variation     = wc_get_product( $variation_id );
		$product_id    = $variation->get_parent_id();
		$product       = wc_get_product( $product_id );

		// This should never happen
		if ( ! $product ) {
			return;
		}

		$content = $this->get_cell_html( $column, $row );

		/**
		* Hook: wc_bulk_variations_table_cell_output.
		*
		* Filters output of each individual cell in the WBV table.
		*
		* @deprecated 2.0.0
		*/
		$content = apply_filters_deprecated( 'wc_bulk_variations_table_cell_output', [ $content, $variation ], '2.0.0', 'wc_bulk_variations_table_cell_content' );

		/**
		* Hook: wc_bulk_variations_table_cell_content.
		*
		* Filters the content of a single cell in the WBV grid.
		*/
		return apply_filters( 'wc_bulk_variations_table_cell_content', $content, $column, $row, $variation, $variation_ids );
	}

	public function get_single_variation_input_attrs( $variation_ids, $is_single_variation ) {
		$classes = [ 'wcbvp-quantity' ];
		$attrs   = [
			'type'  => 'number',
			'name'  => 'input_quantity',
			'value' => 0,
			'title' => _x( 'Qty', 'Product quantity input tooltip', 'woocommerce-bulk-variations' ),
		];

		if ( count( $this->args->attributes ) > 2 ) {
			$attrs['disabled'] = '';
		}

		$variation = wc_get_product( reset( $variation_ids ) );

		if ( $is_single_variation && $variation ) {
			$variation_id = $variation->get_id();
			$stock        = $variation->get_stock_quantity();
			$max          = $stock ? $stock : '';
			$max          = $variation->is_sold_individually() ? 1 : $max;
			$individual   = $variation->is_sold_individually() ? 1 : 0;
			$price        = wc_get_price_to_display( $variation );
			$manage_stock = $variation->get_manage_stock();
			$backorders   = $variation->get_backorders();

			if ( is_numeric( $price ) && $variation->is_in_stock() ) {

				$default = $this->get_default_quantity( $variation );

				if ( $default > $stock && $manage_stock && $backorders === 'no' ) {
					$default = $stock;
				}

				if ( $backorders !== 'no' ) {
					$max = '';
				}

				if ( $max && (int) $max < $default ) {
					$default = $max;
				}

				$default = max( 0, $default );

				$attrs = array_merge(
					$attrs,
					[
						'type'            => 'number',
						'step'            => '1',
						'value'           => $default,
						'title'           => _x( 'Qty', 'Product quantity input tooltip', 'woocommerce-bulk-variations' ),
						'size'            => 4,
						'inputmode'       => 'numeric',
						'data-price'      => $price,
						'data-product_id' => $variation_id,
						'min'             => 0,
					]
				);

				if ( $backorders === 'no' || $individual ) {
					$attrs['max'] = $max;
				}

				if ( $this->args->disable_purchasing || ! $variation->is_purchasable() ) {
					$attrs['disabled'] = '';
				}

				$attrs = apply_filters( 'woocommerce_quantity_input_args', $attrs, $variation );

				// the standard woocommerce_quantity_input_args filter may introduce classes we don't want, just remove them
				if ( isset( $attrs['classes'] ) ) {
					unset( $attrs['classes'] );
				}
			}
		}

		if ( $variation && ! $variation->is_in_stock() && 'no' === $variation->get_backorders() ) {
			$attrs['max']      = 0;
			$attrs['disabled'] = '';
		}

		$attrs['class'] = implode( ' ', $classes );
		$attrs          = $this->convert_wc_input_attrs( $attrs );

		/**
		* Hook: wc_bulk_variations_qty_input_args.
		*
		* Filters all of the input arguments before they are converted to attribute pairs. If you need
		* to add a class to the input, this is where you'd do it.
		*/
		$attrs = apply_filters( 'wc_bulk_variations_qty_input_args', $attrs, $variation );

		return $attrs;
	}

	public function get_available_variations() {
		if ( empty( $this->available_variations ) ) {
			$product = wc_get_product( $this->args->include );

			$this->available_variations = array_filter(
				array_map(
					function( $variation_id ) use ( $product ) {
						$variation = wc_get_product( $variation_id );

						if ( $variation ) {
							return apply_filters(
								'woocommerce_available_variation',
								[
									'attributes'            => $variation->get_variation_attributes(),
									'availability_html'     => wc_get_stock_html( $variation ),
									'display_price'         => wc_get_price_to_display( $variation ),
									'display_regular_price' => wc_get_price_to_display( $variation, [ 'price' => $variation->get_regular_price() ] ),
									'is_downloadable'       => $variation->is_downloadable(),
									'is_in_stock'           => $variation->is_in_stock(),
									'is_purchasable'        => $variation->is_purchasable(),
									'max_qty'               => 0 < $variation->get_max_purchase_quantity() ? $variation->get_max_purchase_quantity() : '',
									'min_qty'               => $variation->get_min_purchase_quantity(),
									'variation_id'          => $variation->get_id(),
									'image'                 => wc_get_product_attachment_props( $variation->get_image_id() ),
									'image_id'              => $variation->get_image_id(),
								],
								$product,
								$variation
							);
						}

						return false;
					},
					$product->get_children()
				)
			);
		}

		return $this->available_variations;
	}

	public function get_filtered_available_variations( $variation_ids ) {
		$available_variations = $this->get_available_variations();

		// Filter only the variations relevant to the current $column and $row.
		return array_values(
			array_filter(
				$available_variations,
				function( $av ) use ( $variation_ids ) {
					return in_array( $av['variation_id'], $variation_ids, true );
				}
			)
		);
	}

	public function get_extra_attribute_dropdown_html() {
		$product = wc_get_product( $this->args->include );

		if ( ! $product ) {
			return false;
		}

		ob_start();

		foreach ( $this->args->extra_attributes as $attribute ) {
			wc_dropdown_variation_attribute_options(
				[
					'product'          => $product,
					'attribute'        => $attribute['name'],
					'class'            => 'wcbvp-additional-attribute',
					'show_option_none' => wc_attribute_label( $attribute['name'] ),
				]
			);
		}

		return ob_get_clean();
	}

	public function get_cell_html( $column, $row ) {
		$variation_ids       = $this->args->get_cell_variation_ids( $column, $row );
		$is_single_variation = 1 === count( $variation_ids );
		$variation           = null;

		if ( $is_single_variation ) {
			$variation = wc_get_product( reset( $variation_ids ) );

			if ( $variation && ! $this->args->is_variation_visible( $variation ) ) {
				return '';
			}
		}

		ob_start();

		do_action( 'wc_bulk_variations_before_cell_content', $column, $row, $variation_ids, $is_single_variation, $variation );

		if ( ! $this->args->disable_purchasing || count( $this->args->attributes ) > 2 ) {

			?>

			<div class="wcbvp-form-variation">
				<?php

				if ( count( $this->args->attributes ) > 2 && ( ! $this->args->has_fast_pool || 'expanded' === $this->args->grid_mode ) ) {
					echo $this->get_extra_attribute_dropdown_html(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				if ( ! $this->args->disable_purchasing ) {
					?>

					<div class="wcbvp-quantity-field">
						<?php echo $this->get_quantity_input_html( $variation_ids, $is_single_variation ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>

					<?php
				}
				?>
			</div>

			<?php

		}

		do_action( 'wc_bulk_variations_before_cell_price', $column, $row, $variation_ids, $is_single_variation, $variation );

		$variations_price_range = array_reduce(
			$this->get_available_variations(),
			function( $r, $v ) {
				$r['min'] = min( $r['min'], $v['display_price'] );
				$r['max'] = max( $r['max'], $v['display_price'] );
				return $r;
			},
			[
				'min' => INF,
				'max' => 0,
			]
		);

		$is_product_same_price = $variations_price_range['min'] === $variations_price_range['max'];
		$price_range_html      = apply_filters( 'wc_bulk_variations_get_cell_price_range_html', $this->get_price_range_html( $variation_ids ), $variations_price_range, $variation_ids );
		$default_price         = sprintf(
			'<div class="price" data-default="%2$s">%1$s</div>',
			$price_range_html,
			htmlentities( $price_range_html )
		);

		if ( ! $this->args->hide_same_price || ! $is_product_same_price || $this->args->disable_purchasing ) {
			if ( $variation ) {
				echo sprintf(
					'<div class="price">%s</div>',
					$variation->get_price_html() //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			} else {
				echo $default_price; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		do_action( 'wc_bulk_variations_before_cell_stock', $column, $row, $variation_ids, $is_single_variation, $variation );

		$stock = '';

		if ( $variation && count( $this->args->attributes ) < 3 && ( $this->args->show_stock || ! $variation->is_in_stock() || 'discontinued' === $variation->get_stock_status() ) ) {
			$stock = $this->get_stock_html( $variation ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		printf(
			'<div class="stock">%s</div>',
			$stock // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		do_action( 'wc_bulk_variations_before_cell_description', $column, $row, $variation_ids, $is_single_variation, $variation );

		$description = '';

		if ( $variation && count( $this->args->attributes ) < 3 && $this->args->show_description ) {
			$description = $variation->get_description(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		printf(
			'<div class="stock">%s</div>',
			$description // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		do_action( 'wc_bulk_variations_after_cell_content', $column, $row, $variation_ids, $is_single_variation, $variation );

		return ob_get_clean();
	}

	public function get_quantity_input_html( $variation_ids, $is_single_variation = false ) {
		$attrs = $this->get_single_variation_input_attrs( $variation_ids, $is_single_variation );

		$input = sprintf(
			'<input %s />',
			wc_implode_html_attributes( $attrs )
		);

		$variation = false;

		if ( $is_single_variation ) {
			$variation = wc_get_product( reset( $variation_ids ) );

			// temporary solution until WDP is adapted to WBV 2.0
			if ( class_exists( 'Barn2\Plugin\WC_Discontinued_Products\Integration\Bulk_Variations' ) ) {
				$integration = wdp()->get_service( 'integration_bulk_variations' );
				remove_filter( 'wc_bulk_variations_qty_input_html', [ $integration, 'display_status' ], 15 );
			}

			/**
			* Hook: wc_bulk_variations_qty_input_html.
			*
			* Filters output of each individual the quantity <input> in the WBV table.
			*/
			return apply_filters( 'wc_bulk_variations_qty_input_html', $input, $attrs, $variation );
		}

		return $input;
	}

	public function get_stock_html( $product ) {

		$availability = $product->get_availability();

		$stock_message = apply_filters_deprecated(
			'wc_bulk_variations_stock_message',
			[
				$availability['availability'],
				$product,
			],
			'1.2',
			'woocommerce_get_availability_text'
		);

		$stock_html = wc_get_stock_html( $product );

		return apply_filters_deprecated(
			'wc_bulk_variations_stock_message_html',
			[
				$stock_html,
				$stock_message,
				$product,
			],
			'1.2',
			'woocommerce_get_stock_html'
		);

	}

	public function get_price_range_html( $variation_ids ) {
		$variations = $this->get_filtered_available_variations( $variation_ids );

		$product_price_range = array_reduce(
			$variations,
			function( $r, $v ) {
				$r['min'] = min( $r['min'], $v['display_price'] );
				$r['max'] = max( $r['max'], $v['display_price'] );
				return $r;
			},
			[
				'min' => INF,
				'max' => 0,
			]
		);

		$price_range_html = wc_price( $product_price_range['min'] );
		$first_id         = reset( $variation_ids );
		$first_variation  = wc_get_product( $first_id );

		if ( $first_variation ) {
			$price_range_html = $first_variation->get_price_html();
		}

		if ( $product_price_range['min'] !== $product_price_range['max'] ) {
			$price_range_html = sprintf(
				'%1$s &ndash; %2$s',
				wc_price( $product_price_range['min'] ),
				wc_price( $product_price_range['max'] )
			);
		}

		return $price_range_html;
	}

	//phpcs:ignore Squiz.Commenting.FunctionComment.MissingParamTag
	/**
	 * The woocommerce_quantity_input_args filter has all sorts of custom names for attributes that
	 * get hardcoded into the template, this function massages those array keypairs into their
	 * final form.
	 */
	private function convert_wc_input_attrs( $attrs ) {

		foreach ( $attrs as $key => $value ) {
			$newkey = null;
			if ( strpos( $key, 'input_' ) === 0 ) {
				$newkey = substr( $key, 6 );
			} elseif ( strrpos( $key, '_value' ) === strlen( $key ) - 6 ) {
				$newkey = substr( $key, 0, -6 );
			}
			if ( $newkey ) {
				unset( $attrs[ $key ] );
				$attrs[ $newkey ] = $value;
			}
		}

		return $attrs;

	}

}
