<?php

namespace Barn2\Plugin\WC_Bulk_Variations;

use Barn2\Plugin\WC_Bulk_Variations\Data\Data_Factory;

/**
 * The main Table class.
 *
 * Responsible for creating the products grid from the specified args and returning the
 * complete grid.
 *
 * The main functions provided are get_grid() and get_data().
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Grid {

	public $id;
	private $product_id;
	private $grid;
	private $attributes;

	private $has_column_images = false;

	/**
	 * Helper classes
	 * phpcs:ignore Squiz.Commenting.VariableComment.MissingVar
	 */
	public $args;
	private $data_factory;

	/**
	 * Internal flags
	 * phpcs:ignore Squiz.Commenting.VariableComment.MissingVar
	 */
	private $grid_initialised = false;
	private $cells_added      = false;

	private $current_row = 0;

	private $use_table_tags;

	public function __construct( $id, $args = [] ) {
		$this->id             = $id;
		$this->args           = new Args( $args );
		$this->data_factory   = new Data_Factory( $this->args );
		$this->product_id     = isset( $this->args->include ) ? $this->args->include : 0;
		$this->use_table_tags = false;
	}

	private function get_table_tag( $type ) {
		if ( ! $this->use_table_tags ) {
			return 'div';
		}

		return $type;
	}

	public function add_attribute( $name, $value ) {
		$this->attributes[ $name ] = $value;
	}

	public function render_grid( $content_only = false ) {
		$content = sprintf( '<%s class="wcbvp-head-group" role="rowgroup">', $this->get_table_tag( 'thead' ) );

		foreach ( $this->grid as $index => $row ) {
			$cells = '';

			foreach ( $row as $cell ) {
				$cells .= $this->render_cell( $cell );
			}

			$class    = trim( implode( ' ', [ 'wcbvp-row', 0 === ( ( $index + $this->has_column_images ) % 2 ) ? '' : 'wcbvp-alt' ] ) );
			$content .= sprintf( '<%3$s class="%1$s" role="row">%2$s</%3$s>', $class, $cells, $this->get_table_tag( 'tr' ) );

			if ( (int) $this->has_column_images === $index ) {
				$content .= sprintf( '</%1$s><%2$s class="wcbvp-row-group" role="rowgroup">', $this->get_table_tag( 'thead' ), $this->get_table_tag( 'tbody' ) );
			}
		}

		$content .= sprintf( '</%s>', $this->get_table_tag( 'tbody' ) );

		if ( $content_only ) {
			return $content;
		}

		return sprintf(
			'<%3$s %1$s>%2$s</%3$s>',
			wc_implode_html_attributes( $this->attributes ),
			$content,
			$this->get_table_tag( 'table' )
		);
	}

	public function reset() {
		$this->attributes = [];

		$this->reset_data();
	}

	public function reset_data() {
		$this->content = [];
	}

	public function render_cell( $cell ) {

		return sprintf(
			'<%3$s %1$s>%2$s</%3$s>',
			wc_implode_html_attributes( $cell['attributes'] ),
			$cell['content'],
			$this->get_table_tag( 'td' )
		);
	}

	public function get_add_to_cart_text() {
		if ( $this->product_id ) {
			$product_obj = wc_get_product( $this->product_id );

			if ( $product_obj ) {
				$text = $product_obj->single_add_to_cart_text();
				return $text;
			}
		}

		return __( 'Add to cart', 'woocommerce' );
	}

	public function render() {
		if ( ! wc_get_product( $this->product_id ) ) {
			return '';
		}

		if ( ! $this->grid_initialised ) {
			// Add attriutes and grid headers.
			$this->add_attributes();
			$this->add_headers();

			// Get the data populating the grid.
			$this->add_cells();

			do_action( 'wc_bulk_variations_table_after_get_table', $this );

			$this->grid_initialised = true;
		}

		$classes = [
			'wcbvp-cart',
			'cart',
			// "wcbvp-{$this->args->grid_mode}-grid-mode",
		];

		if ( count( $this->args->attributes ) > 2 ) {
			// $classes[] = 'wcbvp-multivariation-cells';
		}

		if ( $this->args->has_fast_pool ) {
			// $classes[] = 'wcbvp-fast-pool';
		}

		$attrs  = [
			'class'                   => implode( ' ', $classes ),
			'method'                  => 'post',
			'enctype'                 => 'multipart/form-data',
			'data-attribute_count'    => count( $this->args->attributes ),
			'data-product_variations' => wp_json_encode( $this->data_factory->get_available_variations() ),
		];

		$output = sprintf(
			'<div class="wcbvp-grid-wrapper">%1$s</div><form %2$s>%3$s</form>',
			$this->render_grid(),
			wc_implode_html_attributes( $attrs ),
			$this->add_footer()
		);

		return apply_filters( 'wc_bulk_variations_get_table_output', $output, '', $this );
	}

	private function add_headers() {
		// get the taxonomies for the two grid dimensions
		// 1-attribute products will have only one
		list( $h_dimension, $v_dimension ) = $this->args->dimensions;

		$top_left_content = wc_attribute_label( $h_dimension['name'] );

		if ( $v_dimension ) {
			$top_left_content = '';

			if ( apply_filters( 'wc_bulk_variations_table_has_topleft_labels', false, $this->product_id ) ) {
				$top_left_content = sprintf( '%s / %s', wc_attribute_label( $h_dimension['name'] ), wc_attribute_label( $v_dimension['name'] ) );
			}

			if ( $this->args->has_fast_pool && 'compact' === $this->args->grid_mode ) {
				$top_left_content = $this->data_factory->get_extra_attribute_dropdown_html();
			}
		}

		$this->grid[ $this->current_row ] = [];

		$this->grid[ $this->current_row ][] = [
			'content'    => $top_left_content,
			'role'       => 'columnheader',
			'attributes' => [ 'class' => 'wcbvp-header wcbvp-col-header wcbvp-row-header' ],
		];

		// add the column headers
		if ( count( $this->args->attributes ) > 1 || 'vert' !== $this->args->variation_attribute ) {
			$this->add_dimension_headers( $h_dimension['terms'] );
		} else {
			$this->grid[ $this->current_row ][] = [
				'content'    => apply_filters( 'wc_bulk_variations_single_variation_header', __( 'Price', 'woocommerce-bulk-variations' ) ),
				'role'       => 'columnheader',
				'attributes' => [ 'class' => 'wcbvp-header wcbvp-col-header' ],
			];
			$this->current_row++;
		}
	}

	private function add_dimension_headers( $terms, $type = 'col' ) {
		$first = true;

		foreach ( $terms as $index => $term ) {
			if ( 0 === $term['count'] ) {
				continue;
			}

			$this->grid[ $this->current_row ][] = $this->prepare_header( $term, $type );

			if ( 'col' === $type && in_array( $this->args->variation_images, [ 'col', 'both' ], true ) ) {
				if ( ! isset( $this->grid[ $this->current_row + 1 ] ) ) {
					$this->grid[ $this->current_row + 1 ] = [];
				}

				// translators: the variation name (e.g. blue or small)
				$aria_label = sprintf( __( 'Variation picture for %s', 'woocommerce-bulk-variations' ), $term['name'] );
				$image      = $this->data_factory->get_attribute_image_html( $term['slug'], $aria_label );
				$attrs      = [
					'class'      => trim( implode( ' ', [ 'wcbvp-col-image', 0 === $index % 2 ? '' : 'wcbvp-v-alt' ] ) ),
					'role'       => 'columnheader',
					'aria-label' => $aria_label,
				];

				if ( $first ) {
					$this->grid[ $this->current_row + 1 ][] = [
						'content'    => '',
						'attributes' => [ 'class' => 'wcbvp-colrow-image' ],
					];

					$first = false;
				}

				$this->grid[ $this->current_row + 1 ][] = [
					'content'    => $image,
					'attributes' => $attrs,
				];

				$this->has_column_images = true;
			}

		}

		$this->current_row = count( $this->grid );
	}

	private function add_cells() {
		if ( $this->cells_added ) {
			return;
		}

		// Reset the grid data
		$this->reset_data();

		do_action_deprecated( 'wc_bulk_variations_table_before_get_data', [ $this ], '2.0.0', 'wc_bulk_variations_table_before_add_cells' );
		do_action( 'wc_bulk_variations_table_before_add_cells', $this );

		// get the taxonomies for the two grid dimensions
		// 1-attribute products will have only one
		list( $h_dimension, $v_dimension ) = $this->args->dimensions;

		if ( $v_dimension && isset( $v_dimension['terms'] ) ) {
			// the product has 2 attributes or more
			// start from the second dimension because the cells
			// must be added row by row (horizontal order)
			foreach ( $v_dimension['terms'] as $v_term ) {
				if ( $v_term['count'] > 0 ) {
					$this->current_row++;
					$this->grid[ $this->current_row ] = [];
					$this->add_dimension_headers( [ $v_term ], 'row' );

					foreach ( $h_dimension['terms'] as $h_term ) {
						if ( $h_term['count'] > 0 ) {
							$this->add_cell( $h_term, $v_term );
						}
					}
				}
			}
		} else {
			// the product has 1 attribute only
			$this->current_row++;

			$this->grid[ $this->current_row ] = [];

			if ( 'vert' !== $this->args->variation_attribute ) {
				$this->grid[ $this->current_row ][] = [
					'content'    => apply_filters( 'wc_bulk_variations_single_variation_header', __( 'Price', 'woocommerce-bulk-variations' ) ),
					'attributes' => [ 'class' => 'wcbvp-header wcbvp-row-header' ],
				];
			}

			foreach ( $h_dimension['terms'] as $h_term ) {
				if ( $h_term['count'] > 0 ) {
					if ( 'vert' === $this->args->variation_attribute ) {
						$this->grid[ $this->current_row ][] = $this->prepare_header( $h_term, 'row' );
					}

					$this->add_cell( $h_term );

					if ( 'vert' === $this->args->variation_attribute ) {
						$this->current_row++;
					}
				}
			}
		}

		do_action_deprecated( 'wc_bulk_variations_table_after_get_data', [ $this ], '2.0.0', 'wc_bulk_variations_table_after_add_cells' );
		do_action( 'wc_bulk_variations_table_after_add_cells', $this );

		$this->cells_added = true;
	}

	private function add_cell( $column, $row = null ) {
		list( $h_dimension, $v_dimension ) = $this->args->dimensions;

		$matrix = $this->args->variation_matrix;

		$content = '';
		$r_slug  = '';
		$r_name  = '';

		list( 'slug' => $c_slug, 'name' => $c_name ) = $column;

		if ( $row ) {
			list( 'slug' => $r_slug, 'name' => $r_name ) = $row;
		}

		if ( isset( $matrix[ $c_slug ] ) && isset( $matrix[ $c_slug ][ $r_slug ] ) && ! empty( $matrix[ $c_slug ][ $r_slug ] ) ||
			! $row && isset( $matrix[ $c_slug ] ) && ! empty( $matrix[ $c_slug ] ) ) {

			$content = $this->data_factory->get_cell_content( $c_slug, $r_slug );
		}

		$classes = [
			'wcbvp-cell',
			'wcbvp-cell-' . wc_sanitize_taxonomy_name( $c_slug ),
		];

		if ( $this->is_even_term( $column, 0 ) ) {
			$classes[] = 'wcbvp-v-alt';
		}

		if ( $row ) {
			$classes[] = 'wcbvp-cell-' . wc_sanitize_taxonomy_name( $r_slug );

			if ( $this->is_even_term( $row ) ) {
				$classes[] = 'wcbvp-alt';
			}
		}

		$attrs = [
			'class'              => implode( ' ', $classes ),
			'role'               => 'gridcell',
			'aria-label'         => sprintf( '%s %s', $h_dimension['label'], $c_name ),
			'data-h_taxonomy'    => wc_sanitize_taxonomy_name( $h_dimension['name'] ),
			'data-h_attribute'   => $c_slug,
			'data-h_label'       => $c_name,
			'data-variation_ids' => implode( ',', $this->args->get_cell_variation_ids( $c_slug, $r_slug ) ),
		];

		if ( $row ) {
			$attrs['data-v_taxonomy']  = wc_sanitize_taxonomy_name( $v_dimension['name'] );
			$attrs['data-v_attribute'] = $r_slug;
			$attrs['data-v_label']     = $r_name;
			$attrs['aria-label']       = sprintf( '%s %s, %s %s', $h_dimension['label'], $c_name, $v_dimension['label'], $r_name );
		}

		$this->grid[ $this->current_row ][] = [
			'content'    => $content,
			'attributes' => $attrs,
		];
	}

	private function is_even_term( $term, $dimension_index = 1 ) {
		if ( ! $this->args->dimensions[ $dimension_index ] || empty( $this->args->dimensions[ $dimension_index ] ) ) {
			return false;
		}

		$dimension = $this->args->dimensions[ $dimension_index ];

		$key = array_keys(
			array_filter(
				$dimension['terms'],
				function( $t ) use ( $term ) {
					return $term['slug'] === $t['slug'];
				}
			)
		);

		if ( ! empty( $key ) ) {
			return ! ! ( reset( $key ) % 2 );
		}

		return false;
	}

	private function add_footer() {

		$footer = '';

		if ( ! $this->args->disable_purchasing ) {
			$price            = wc_price( 0 );
			$add_to_cart_text = $this->get_add_to_cart_text();

			ob_start();

			/**
			 * Action hook to add content before the bulk variations totals wrapper.
			 *
			 * @param int $product_id The product ID.
			 * @param Grid $grid The grid object.
			 */
			do_action( 'wc_bulk_variations_before_totals_container', $this->product_id, $this );
			?>

			<div id="wcbvp_wrapper_<?php echo esc_attr( $this->id ); ?>" class="wcbvp-total-wrapper">
				<div class="wcbvp-variation-pool"></div>
				<div class="wcbvp-total-left">
					<p>
						<span class="wcbvp_total_label"><?php esc_html_e( 'Items', 'woocommerce-bulk-variations' ); ?></span>:
						<span class="wcbvp_total_items">0</span>
					</p>
					<p>
						<span class="wcbvp_total_label"><?php esc_html_e( 'Total', 'woocommerce-bulk-variations' ); ?></span>:
						<span class="wcbvp_total_price"><?php echo $price; ?></span>
					</p>
					<label role="status">0 Items, Total $0.00</label>
				</div>
				<div class="wcbvp-total-right">
					<?php echo $this->get_add_to_cart_button_html( $add_to_cart_text ); ?>
				</div>
			</div>

			<?php
			$footer = ob_get_clean();
		}

		return $footer;
	}

	private function get_add_to_cart_button_html( $button_text ) {
		ob_start();
		?>

		<button disabled class="single_add_to_cart_button button alt disabled wc-variation-selection-needed">
			<?php echo esc_html( $button_text ); ?>
		</button>

		<?php

		/**
		 * Filter the HTML markup of the add-to-cart button attached to a bulk variations grid
		 *
		 * @param string $button_html The markup of the button being filtered
		 * @param int $product_id The ID of the current product
		 * @param Grid $grid The object instance of the current Grid class
		 */
		$button = apply_filters( 'wc_bulk_variations_add_to_cart_button_html', ob_get_clean(), $this->product_id, $this );

		return $button;
	}

	private function prepare_header( $term, $type = 'col' ) {

		$term_slug = wc_sanitize_taxonomy_name( $term['slug'] );
		$classes   = [
			'wcbvp-header',
			"wcbvp-{$type}-header",
			"product-{$type}-{$term_slug}",
		];

		if ( $this->is_even_term( $term, 'row' === $type ? 1 : 0 ) ) {
			$classes[] = 'row' === $type ? 'wcbvp-alt' : 'wcbvp-v-alt';
		}

		if ( 'row' !== $type ) {
			$classes[] = "col-{$term_slug}";
		}

		$classes = apply_filters_deprecated( "wc_bulk_variations_table_{$type}_class", [ $classes ], '2.0.0', "wc_bulk_variations_table_{$type}_class_{%term_slug%}" );
		$classes = apply_filters( "wc_bulk_variations_table_{$type}_class_{$term['slug']}", $classes );

		$attrs = [
			'class'          => implode( ' ', $classes ),
			'role'           => 'col' === $type ? 'columnheader' : 'rowheader',
			'aria-label'     => $term['name'],
			'data-attribute' => $term['slug'],
			'data-label'     => $term['name'],
		];

		$image = '';

		if ( 'row' === $type || ( 1 === count( $this->args->attributes ) && 'vert' === $this->args->variation_attribute ) ) {
			if ( $type === $this->args->variation_images ||
				1 === count( $this->args->attributes ) && in_array( $type, [ 'col', 'row' ], true ) ||
				'both' === $this->args->variation_images ) {

				$aria_label = sprintf( __( 'Variation picture for %s', 'woocommerce-bulk-variations' ), $term['name'] );
				$image      = $this->data_factory->get_attribute_image_html( $term['slug'], $aria_label );
			}
		}

		$header_template = '<div class="wcbvp-header-block">%1$s<span class="%3$s">%2$s</span></div>';

		$content = sprintf(
			$header_template,
			$image,
			$this->non_breakable( $term['name'] ),
			$term['name'],
			$image ? 'with-image' : 'no-image'
		);

		return [
			'content'    => $content,
			'attributes' => $attrs,
		];
	}

	private function non_breakable( $string ) {
		return str_replace( [ '-', '–' ], '&#x2011;', $string );
	}

	private function get_grid_style_attribute() {
		$styles = [];

		list( $h_dimension, $v_dimension ) = $this->args->dimensions;

		$col = count(
			array_filter(
				$h_dimension['terms'],
				function( $t ) {
					return $t['count'] > 0;
				}
			)
		);

		$row = 1;

		if ( isset( $v_dimension['terms'] ) ) {
			$row = count(
				array_filter(
					$v_dimension['terms'],
					function( $t ) {
						return $t['count'] > 0;
					}
				)
			);
		}

		if ( empty( $v_dimension ) && 'vert' === $this->args->variation_attribute ) {
			$col--;
		}

		$styles['--h'] = $row;
		$styles['--v'] = $col;

		return implode(
			';',
			array_map(
				function( $k, $v ) {
					return "$k:$v";
				},
				array_keys( $styles ),
				$styles
			)
		);
	}

	private function add_attributes() {

		list( $h_dimension, $v_dimension ) = $this->args->dimensions;

		// Set grid attributes.
		$classes = [
			'wc-bulk-variations-table',
			'wc-bulk-variations-grid',
			'nowrap',
		];

		// if ( empty( $v_dimension ) ) {
		// 	if ( $this->args->variation_attribute ) {
		// 		$classes[] = $this->args->variation_attribute;
		// 	} else {
		// 		$classes[] = 'headless';
		// 	}
		// }

		if ( 'off' !== $this->args->variation_images ) {
			$classes[] = "{$this->args->variation_images}-header-images";
		}

		if ( ! $this->use_table_tags ) {
			$classes[] = 'wcbvp-table';
		}

		$classes = array_filter(
			array_merge(
				$classes,
				[ trim( apply_filters( 'wc_bulk_variations_table_custom_class', '', $this ) ) ]
			)
		);

		$this->attributes = [
			'id'    => $this->id,
			'role'  => 'grid',
			'class' => implode( ' ', apply_filters( 'wc_bulk_variations_table_classes', $classes, $this ) ),
			'style' => $this->get_grid_style_attribute(),
		];
	}
}
