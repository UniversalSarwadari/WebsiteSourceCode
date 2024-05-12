<?php
namespace Barn2\Plugin\WC_Bulk_Variations\Admin;

use Barn2\WBV_Lib\Registerable,
	WP_Query,
	Automattic\WooCommerce\Utilities\NumberUtil;

use const Barn2\Plugin\WC_Bulk_Variations\PLUGIN_FILE;

class Variation_Manager implements Registerable {

	/**
	 * The path of the HTML views used by this class
	 *
	 * @var string
	 */
	private $views_path;

	public function __construct() {
		$this->views_path = plugin_dir_path( PLUGIN_FILE ) . 'src/Admin/views/';
	}

	public function register() {
		add_action( 'woocommerce_bulk_edit_variations_default', [ $this, 'bulk_edit_variations' ], 10, 4 );
		add_action( 'woocommerce_variable_product_before_variations', [ $this, 'print_variation_filters' ] );
		add_action( 'woocommerce_variable_product_bulk_edit_actions', [ $this, 'add_thumbnail_bulk_actions' ] );
		
		// add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'handle_custom_query_var' ], 10, 2 );

		$this->add_ajax_events();
	}

	private function add_ajax_events() {
		$ajax_events = [
			'load_variations',
			'do_action',
		];

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_wcbvp_' . $ajax_event, [ $this, $ajax_event ] );
		}
	}

	public function bulk_edit_variations( $bulk_action, $data ) {
		$bulk_action   = str_replace( [ 'variable_', 'toggle_', '_selected' ], '', $bulk_action );
		$field         = $bulk_action;
		$query         = $this->get_variation_query( $data );
		$variations    = (array) $query->posts;
		$variation_ids = array_column( $variations, 'ID' );

		if ( 0 === count( $variation_ids ) ) {
			return;
		}

		switch ( $bulk_action ) {
			case 'delete_all':
				if ( $data['allowed'] ) {
					foreach ( $variation_ids as $variation_id ) {
						$variation = wc_get_product( $variation_id );
						$variation->delete( true );
					}
				}

				break;

			case 'enabled':
				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					$variation->set_status( 'private' === $variation->get_status( 'edit' ) ? 'publish' : 'private' );
					$variation->save();
				}

				break;

			case 'regular_price':
			case 'sale_price':
			case 'stock':
			case 'download_limit':
			case 'download_expiry':
			case 'length':
			case 'width':
			case 'height':
			case 'weight':
				if ( ! isset( $data['value'] ) ) {
					return;
				}

				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					$variation->{ "set_$field" }( wc_clean( $data['value'] ) );
					$variation->save();
				}

				break;

			case 'regular_price_increase':
			case 'regular_price_decrease':
			case 'sale_price_increase':
			case 'sale_price_decrease':
				if ( ! isset( $data['value'] ) ) {
					return;
				}

				$value     = $data['value'];
				$field     = str_replace( [ '_increase', '_decrease' ], '', $bulk_action );
				$direction = 'increase' === str_replace( "{$field}_", '', $bulk_action ) ? 1 : -1;

				foreach ( $variation_ids as $variation_id ) {
					$variation   = wc_get_product( $variation_id );
					$field_value = $variation->{"get_$field"}( 'edit' );

					if ( '%' === substr( $value, -1 ) ) {
						$percent      = wc_format_decimal( substr( $value, 0, -1 ) );
						$field_value += NumberUtil::round( ( $field_value / 100 ) * $percent, wc_get_price_decimals() ) * $direction;
					} else {
						$field_value += $value * $direction;
					}

					$variation->{"set_$field"}( $field_value );
					$variation->save();
				}

				break;

			case 'stock':
			case 'low_stock_amount':
				if ( ! isset( $data['value'] ) ) {
					return;
				}

				if ( 'stock' === $field ) {
					$field = 'stock_quantity';
				}

				$quantity = wc_stock_amount( wc_clean( $data['value'] ) );

				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation->managing_stock() ) {
						$variation->{ "set_$field" }( $quantity );
					} else {
						$variation->{ "set_$field" }( null );
					}
					$variation->save();
				}

				break;

			case 'stock_status_instock':
			case 'stock_status_outofstock':
			case 'stock_status_onbackorder':
			case 'stock_status_discontinued':
				$value = str_replace( 'stock_status_', '', $field );
				$field = 'stock_status';

				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					$variation->{ "set_$field" }( wc_clean( $value ) );
					$variation->save();
				}

				break;

			case 'downloadable':
			case 'virtual':
			case 'manage_stock':
				foreach ( $variation_ids as $variation_id ) {
					$variation  = wc_get_product( $variation_id );
					$prev_value = $variation->{ "get_$field" }( 'edit' );
					$variation->{ "set_$field" }( ! $prev_value );
					$variation->save();
				}

				break;

			case 'sale_schedule':
				if ( ! isset( $data['date_from'] ) && ! isset( $data['date_to'] ) ) {
					return;
				}

				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );

					if ( 'false' !== $data['date_from'] ) {
						$variation->set_date_on_sale_from( wc_clean( $data['date_from'] ) );
					}

					if ( 'false' !== $data['date_to'] ) {
						$variation->set_date_on_sale_to( wc_clean( $data['date_to'] ) );
					}

					$variation->save();
				}

				break;

			case 'remove_thumbnail':
				if ( ! isset( $data['allowed'] ) || ! $data['allowed'] ) {
					return;
				}

				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					$variation->set_image_id();
					$variation->save();
				}

				break;

			case 'set_thumbnail':
				if ( ! isset( $data['thumbnail_id'] ) || ! wp_attachment_is_image( $data['thumbnail_id'] ) ) {
					return;
				}

				$thumbnail_id = (int) $data['thumbnail_id'];

				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					$variation->set_image_id( $thumbnail_id );
					$variation->save();
				}

				break;
			
			default:
				/**
				 * Perform a custom bulk action on the filtered variations
				 * 
				 * WooCommerce has already an action hook to extend the bulk actions different than the factory ones
				 * Any plugin could already use that hook to define a custom action.
				 * Nevertheless, WBV also adds a custom query to get the affected variations based on the filters.
				 * For this reason we add this action which also passes the resulting filtered variations
				 * to any plugin that want to apply their custom bulk actions to the filtered variations only
				 * 
				 * @param string $bulk_action The action being run
				 * @param array $data The POST data passed to the bulk action process
				 * @param array $variation_ids The array with the IDs of the variations being affected
				 */
				do_action( 'wc_bulk_variations_bulk_edit_variations_default', $bulk_action, $data, $variation_ids );
		}
	}

	public function print_variation_filters() {
		global $post;

		$product = wc_get_product( $post->ID );

		if ( ! is_a( $product, 'WC_Product_Variable' ) ) {
			return false;
		}

		include "{$this->views_path}html-meta-boxes-filters.php";
	}

	public function get_variation_query( $post_data ) {
		// Set $post global so its available, like within the admin screens.
		global $post;

		if ( is_a( $post, 'WP_Post' ) ) {
			$product_id = $post->ID;
		}

		if ( isset( $post_data['product_id'] ) ) {
			$product_id = absint( $post_data['product_id'] );
			$post       = get_post( $product_id ); // phpcs:ignore
		}

		$per_page    = ! empty( $post_data['per_page'] ) ? (int) $post_data['per_page'] : 10;
		$page        = ! empty( $post_data['page'] ) ? absint( $post_data['page'] ) : 1;
		$filters     = isset( $post_data['filters'] ) ? $post_data['filters'] : [];
		$post_status = [ 'private', 'publish' ];

		// the following section defines the variation attributes that are part of the query
		$attributes = isset( $filters['attributes'] ) ? $filters['attributes'] : [];
		$attributes = array_combine(
			array_map(
				function( $k ) {
					return str_replace( 'attribute_', '', $k );
				},
				array_keys( $attributes )
			),
			$attributes
		);
		$meta_query = [
			'relation' => 'AND',
		];

		foreach ( $attributes as $attribute => $term ) {
			$attribute = str_replace( 'attribute_', '', $attribute );

			if ( $term ) {
				$meta_query[] = [
					'key'     => "attribute_$attribute",
					'value'   => $term,
					'compare' => '=',
				];
			}
		}

		if ( isset( $filters['meta'] ) ) {

			// the following section adds any additional meta_key
			foreach ( $filters['meta'] as $filter ) {
				$key     = $filter['key'];
				$value   = isset( $filter['value'] ) ? $filter['value'] : null;
				$value2  = isset( $filter['value2'] ) ? $filter['value2'] : null;
				$compare = $filter['compare'];
				$type    = 'CHAR';

				switch ( $compare ) {
					case 'BETWEEN':
					case 'NOT BETWEEN':
						$value = [ $value, $value2 ];

						break;

					case 'NOT LIKE':
						$meta_query[] = [
							'relation' => 'OR',
							[
								'key'     => $key,
								'value'   => $value,
								'compare' => $compare,
							],
							[
								'key'     => $key,
								'compare' => 'NOT EXISTS',
							]
						];

						// meta_query already added
						// set $key to null so it is not added again later
						$key = null;

						break;
				}

				switch ( $key ) {
					case '_regular_price':
					case '_sale_price':
					case '_stock':
					case '_low_stock_amount':
					case '_download_limit':
					case '_download_expiry':
					case '_length':
					case '_width':
					case '_height':
					case '_weight':
						$type = 'NUMERIC';

						break;

					case '_downloadable':
					case '_virtual':
					case '_manage_stock':
						$compare = '=';
						if ( 'on' === $filter['compare'] ) {
							$value = 'yes';
						} else {
							$value = 'no';
						}

						break;

					case '_stock_status':
						// $value = explode( ',', $value );

						break;

					case '_sale_price_dates':
						$type = 'NUMERIC';
						// a BETWEEN query is handled differently for sale schedule
						// so $value needs to be reset to the original value
						$value = isset( $filter['value'] ) ? strtotime( $filter['value'] ) : 0;

						$from_date_meta_query = [];
						$to_date_meta_query   = [];

						$date_schedule_relation = 'AND';

						if ( in_array( $compare, [ 'BETWEEN', 'NOT BETWEEN' ], true ) ) {
							$to_date_meta_query = [
								'key'     => '_sale_price_dates_to',
								'value'   => strtotime( $value2 ),
								'compare' => 'BETWEEN' === $compare ? '>' : '<',
								'type'    => $type,
							];

							$key     = '_sale_price_dates_from';
							$compare = 'BETWEEN' === $compare ? '<' : '>';
						} elseif ( in_array( $compare, [ 'running', 'not_running' ], true ) ) {
							$value = time();

							$to_date_meta_query = [
								'key'     => '_sale_price_dates_to',
								'value'   => $value,
								'compare' => 'running' === $compare ? '>' : '<',
								'type'    => $type,
							];

							$date_schedule_relation = 'running' === $compare ? 'AND' : 'OR';

							$key     = '_sale_price_dates_from';
							$compare = 'running' === $compare ? '<' : '>';
						} else {
							$key     = false !== strpos( $compare, 'starts' ) ? '_sale_price_dates_from' : '_sale_price_dates_to';
							$compare = false !== strpos( $compare, 'before' ) ? '<' : '>';
						}

						if ( $value ) {
							$from_date_meta_query = [
								'key'     => $key,
								'value'   => $value,
								'compare' => $compare,
								'type'    => $type,
							];
						}

						$date_meta_query = [
							'relation' => $date_schedule_relation,
						];

						if ( ! empty( $from_date_meta_query ) ) {
							$date_meta_query[] = $from_date_meta_query;
						}

						if ( ! empty( $to_date_meta_query ) ) {
							$date_meta_query[] = $to_date_meta_query;
						}

						$sale_schedule_meta_query = [
							'relation' => 'OR',
							$date_meta_query,
						];

						if ( 'running' === $filter['compare'] ) {
							$sale_query = [
								'relation' => 'AND',
								[
									'key'     => '_sale_price',
									'compare' => 'EXISTS',
								],
								[
									'relation' => 'OR',
									$sale_schedule_meta_query,
									[
										[
											'key'     => '_sale_price_dates_from',
											'compare' => 'NOT EXISTS',
										],
										[
											'key'     => '_sale_price_dates_to',
											'compare' => 'NOT EXISTS',
										],
									],
								]
							];
						} elseif ( 'not_running' === $filter['compare'] ) {
							$sale_query = [
								'relation' => 'OR',
								[
									'key'     => '_sale_price',
									'compare' => 'NOT EXISTS',
								],
								$sale_schedule_meta_query
							];
						} else {
							$sale_query = [
								'key'     => '_sale_price',
								'compare' => 'EXISTS',
							];
						}

						$meta_query[] = $sale_query;

						// meta_query already added
						// set $key to null so it is not added again later
						$key = null;

						break;

					case '_enabled':
						$post_status = 'on' === $filter['compare'] ? [ 'publish' ] : [ 'private' ];

						// no need to add a meta_query
						// set $key to null so it is not added later
						$key = null;

						break;
				}

				// nullifying $key is a way of short-circuiting the addition of the meta query
				// in case it was necessary to add it earlier
				if ( $key ) {
					$meta_query[] = [
						'key'     => $key,
						'value'   => $value,
						'compare' => $compare,
						'type'    => $type,
					];
				}
			}
		}

		$args = apply_filters(
			'wc_bulk_variations_bulk_query_args',
			[
				'post_status'    => $post_status,
				'post_type'      => 'product_variation',
				'post_parent'    => $product_id,
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => [
					'menu_order' => 'ASC',
					'ID'         => 'DESC',
				],
				'meta_query'     => $meta_query,
			],
			$post_data
		);

		return new WP_Query( $args );
	}

	public function load_variations() {
		check_ajax_referer( 'load-variations', 'security' );

		if ( ! current_user_can( 'edit_products' ) || empty( $_POST['product_id'] ) ) {
			wp_die( -1 );
		}

		$product_id     = absint( $_POST['product_id'] );
		$loop           = 0;
		$product_object = wc_get_product( $product_id );
		$children_count = count( $product_object->get_children() );

		$query      = $this->get_variation_query( $_POST );
		$variations = $query->posts;

		ob_start();

		if ( $variations ) {
			wc_render_invalid_variation_notice( $product_object );

			foreach ( $variations as $variation ) {
				$variation_id        = $variation->ID;
				$variation_object    = wc_get_product( $variation_id );
				$_product_attributes = get_post_meta( $variation_id, '_product_attributes', true );
				$_default_attributes = get_post_meta( $variation_id, '_default_attributes', true );
				$variation_data      = array_merge( get_post_custom( $variation_id ), wc_get_product_variation_attributes( $variation_id ) ); // kept for BW compatibility.
				include WC()->plugin_path() . '/includes/admin/meta-boxes/views/html-variation-admin.php';
				$loop++;
			}
		}

		$html = ob_get_clean();

		wp_send_json_success(
			[
				'html'  => $html,
				'count' => $query->found_posts,
				'total' => $children_count,
				'pages' => $query->max_num_pages
			]
		);

		wp_die();
	}

	public function add_thumbnail_bulk_actions() {
		?>
		<optgroup label="<?php esc_attr_e( 'Thumbnails', 'woocommerce-bulk-variations' ); ?>">
			<option value="variable_set_thumbnail"><?php esc_html_e( 'Set thumbnail', 'woocommerce-bulk-variations' ); ?></option>
			<option value="variable_remove_thumbnail"><?php esc_html_e( 'Remove thumbnail', 'woocommerce-bulk-variations' ); ?></option>
		</optgroup>
		<?php
	}
}
