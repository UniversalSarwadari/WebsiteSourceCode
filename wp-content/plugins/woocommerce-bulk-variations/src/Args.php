<?php

namespace Barn2\Plugin\WC_Bulk_Variations;

use Barn2\Plugin\WC_Bulk_Variations\Util\Util;
use Barn2\Plugin\WC_Bulk_Variations\Util\Settings;

/**
 * Responsible for storing and validating the product variations arguments.
 * Parses an array of args into the corresponding properties.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Args {

	private $args = [];

	/**
	 * The default arguments for the class options.
	 *
	 * @var array The default table parameters
	 */
	public static $default_args = [
		'include'                      => 0,
		'columns'                      => '',
		'rows'                         => '',
		'enable'                       => false,
		'enable_multivariation'        => false,
		'disable_purchasing'           => false,
		'show_stock'                   => false,
		'show_description'             => false,
		'hide_same_price'              => false,
		'images'                       => false,
		'variation_attribute'          => '',
		'image_direction'              => '',
		'variation_images'             => 'off',
		'add_images_to_gallery'        => false,
		'use_lightbox'                 => true,
		'has_images'                   => false,
		'attributes'                   => [],
		'default_horizontal_attribute' => [],
		'default_vertical_attribute'   => [],
		'grid_mode'                    => 'expanded',
		'has_fast_pool'                => false,
	];

	public function __construct( array $args = [] ) {
		$this->set_args( $args );
	}

	public function get_args() {
		return $this->args;
	}

	public function set_args( array $args ) {

		// Update args
		$this->args = array_merge( $this->args, $args );

		// Parse/validate args & update properties
		$this->parse_args( $this->args );
	}

	private function parse_args( array $args ) {

		$defaults = self::get_defaults();

		// Merge in default args.
		$args = wp_parse_args( $args, $defaults );

		$args = $this->set_settings( $args );

		foreach ( $args as $arg_k => $arg_v ) {
			$this->$arg_k = $arg_v;
		}
		unset( $this->args );
	}

	public function set_settings( $args ) {

		$settings = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA );

		$args = $this->process_data( $args );

		return $args;
	}

	public function get_term_thumbnails( $product ) {
		$tt         = [];
		$attributes = $product->get_attributes();

		foreach ( $attributes as $attribute_key => $attribute_data ) {

			$attribute_name = $attribute_data->get_name();

			if ( false !== strpos( $attribute_key, 'pa_' ) ) {

				$product_tax = get_taxonomy( $attribute_key );

				if ( $product_tax && isset( $product_tax->labels->singular_name ) && $product_tax->labels->singular_name ) {
					$attribute_name = $product_tax->labels->singular_name;
				}

				// $terms   = $attribute_data->get_terms();
				$terms      = get_terms( $attribute_data->get_name() );
				$thumbnails = [];

				foreach ( $terms as $term ) {
					$thumbnail_id = get_term_meta( $term->term_id, 'wcbvp-thumbnail', true );

					if ( wp_attachment_is_image( $thumbnail_id ) ) {
						$thumbnails[ $term->slug ] = $thumbnail_id;
					}
				}

				$tt[ $attribute_key ] = $thumbnails;
			}

			$attributes_lbls[ $attribute_key ] = $attribute_name;
		}

		return $tt;
	}

	public function is_variation_visible( $variation ) {
		$is_visible = true;

		if ( ! $variation->is_in_stock() && filter_var( get_option( 'woocommerce_hide_out_of_stock_items' ), FILTER_VALIDATE_BOOLEAN ) ) {
			$is_visible = false;
		};

		if ( ! $variation->is_purchasable() ) {
			$is_visible = false;
		}

		return apply_filters( 'wc_bulk_variations_is_variation_visible', $is_visible, $variation );
	}

	public function get_available_children( $product ) {
		$variations = $product->get_children();

		if ( ! is_array( $variations ) || empty( $variations ) ) {
			return [];
		}

		$variations = array_flip( $variations );

		$variations = array_map(
			function( $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation ) {
					return array_merge(
						[
							'id'        => $variation_id,
							'variation' => $variation
						],
						$variation->get_variation_attributes()
					);
				}
				return [];
			},
			array_keys( $variations )
		);

		return array_filter(
			$variations,
			function( $variation ) {
				return $this->is_variation_visible( $variation['variation'] );
			}
		);
	}

	public function process_data( $args ) {
		$product_id = $args['include'];
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return $args;
		}

		$attributes = $this->get_product_attributes( $product );

		$args['price']           = $product->get_price_html();
		$args['term_thumbnails'] = $this->get_term_thumbnails( $product );

		$children = $this->get_available_children( $product );

		$h_dimension            = [];
		$v_dimension            = [];
		$h_terms                = [];
		$v_terms                = [];
		$extra_attributes       = [];
		$args['attribute_cols'] = [];

		// product has a single attribute
		if ( 1 === count( $attributes ) ) {
			$h_dimension            = reset( $attributes );
			$h_terms                = $h_dimension['terms'];
			$args['attribute_cols'] = count( $h_terms );
		}

		// product has more than 1 attribute => the grid is bidimensional
		// and each cell contains a quantity box only
		if ( count( $attributes ) > 1 ) {
			$key = wc_sanitize_taxonomy_name( $args['columns'] );

			if ( isset( $attributes[ $key ] ) ) {
				$h_dimension = $attributes[ $key ];
				$index       = array_search( $key, array_keys( $attributes ), true );

				if ( false !== $index ) {
					array_splice( $attributes, $index, 1 );
				}
			}

			$key = wc_sanitize_taxonomy_name( $args['rows'] );

			if ( isset( $attributes[ $key ] ) ) {
				$v_dimension = $attributes[ $key ];
				$index       = array_search( $key, array_keys( $attributes ), true );

				if ( false !== $index ) {
					array_splice( $attributes, $index, 1 );
				}
			}

			// if `$h_dimension` or `$v_dimension` are still empty
			// define them as whatever is left in the $attributes array
			if ( empty( $h_dimension ) ) {
				$h_dimension = array_slice( $attributes, 0, 1 );
				$h_dimension = reset( $h_dimension );
				$attributes  = array_slice( $attributes, 1 );
			}

			if ( empty( $v_dimension ) ) {
				$v_dimension = array_slice( $attributes, 0, 1 );
				$v_dimension = reset( $v_dimension );
				$attributes  = array_slice( $attributes, 1 );
			}

			$h_terms                = $h_dimension['terms'];
			$v_terms                = $v_dimension['terms'];
			$args['attribute_cols'] = count( $h_terms );
			$args['attribute_rows'] = count( $v_terms );
		}

		// any attribute left is assigned to the $extra_attributes array
		// that will populate each cell with the appropriate variation select boxes
		$extra_attributes = $attributes;

		$variation_matrix = [];

		foreach ( $h_terms as $h_index => $h_term ) {
			$variation_matrix[ $h_term['slug'] ] = [];

			$h_key = wc_sanitize_taxonomy_name( "attribute_{$h_dimension['name']}" );

			if ( $v_dimension ) {
				// the product has more than 1 attribute
				// the matrix is going to be a bidimensional array
				foreach ( $v_terms as $v_index => $v_term ) {
					$v_key = wc_sanitize_taxonomy_name( "attribute_{$v_dimension['name']}" );

					$variation_matrix[ $h_term['slug'] ][ $v_term['slug'] ] = array_filter(
						array_map(
							function( $c ) {
								return $c['id'];
							},
							array_values(
								array_filter(
									$children,
									function( $c ) use ( $h_key, $v_key, $h_term, $v_term ) {
										$hk = sanitize_title( rawurlencode( $h_key ) );
										$vk = sanitize_title( rawurlencode( $v_key ) );

										return $c[ $hk ] === $h_term['slug'] && $c[ $vk ] === $v_term['slug'];
									}
								)
							)
						)
					);

					if ( ! isset( $v_dimension['terms'][ $v_index ]['count'] ) ) {
						$v_dimension['terms'][ $v_index ]['count'] = 0;
					}

					// accumulate the number of children each column term has
					$v_dimension['terms'][ $v_index ]['count'] += count( $variation_matrix[ $h_term['slug'] ][ $v_term['slug'] ] );

					if ( ! isset( $h_dimension['terms'][ $h_index ]['count'] ) ) {
						$h_dimension['terms'][ $h_index ]['count'] = 0;
					}

					// accumulate the number of children each row term has
					$h_dimension['terms'][ $h_index ]['count'] += count( $variation_matrix[ $h_term['slug'] ][ $v_term['slug'] ] );
				}
			} else {
				// the product has just 1 attribute
				$variation_matrix[ $h_term['slug'] ] = array_filter(
					array_map(
						function( $c ) {
							return $c['id'];
						},
						array_values(
							array_filter(
								$children,
								function( $c ) use ( $h_key, $h_term ) {
									$hk = sanitize_title( rawurlencode( $h_key ) );
									return $c[ $hk ] === $h_term['slug'];
								}
							)
						)
					)
				);

				if ( ! isset( $h_dimension['terms'][ $h_index ]['count'] ) ) {
					$h_dimension['terms'][ $h_index ]['count'] = 0;
				}

				// accumulate the number of children each row term has
				$h_dimension['terms'][ $h_index ]['count'] += count( $variation_matrix[ $h_term['slug'] ] );
			}
		}

		$args['attributes']       = $this->get_product_attributes( $product );
		$args['dimensions']       = [ $h_dimension, $v_dimension ];
		$args['extra_attributes'] = $extra_attributes;
		$args['variation_matrix'] = $variation_matrix;

		return $args;
	}

	public function get_cell_variation_ids( $column, $row = '' ) {
		$ids    = [];
		$matrix = $this->variation_matrix;

		if ( array_key_exists( $column, $matrix ) ) {
			if ( '' !== $row && array_key_exists( $row, $matrix[ $column ] ) ) {
				$ids = $matrix[ $column ][ $row ];
			} else {
				$ids = $matrix[ $column ];
			}
		}

		return $ids;
	}

	public function get_attribute_variation_ids( $attribute ) {
		$ids    = [];
		$matrix = $this->variation_matrix;

		if ( array_key_exists( $attribute, $matrix ) ) {
			$ids = $this->recursive_merge( $matrix[ $attribute ] );
		} else {
			$matrix = $this->recursive_merge( $matrix, true );

			if ( array_key_exists( $attribute, $matrix ) ) {
				$ids = $matrix[ $attribute ];

				if ( ! is_array( $ids ) && ! is_array( reset( $ids ) ) ) {
					$ids = [ $ids ];
				}
			}
		}

		if ( 1 === count( $ids ) && isset( $ids[0] ) && is_array( $ids[0] ) ) {
			$ids = $ids[0];			
		}

		return $ids;
	}

	private function recursive_merge( $array, $force_associative = false ) {
		$array = array_reduce(
			$array,
			function( $r, $v ) use ( $force_associative ) {
				if ( ! is_array( $v ) ) {
					$v = [ $v ];
				}

				$keys = array_keys( $v );

				$v = array_combine(
					array_map(
						function( $k ) use ( $force_associative ) {
							if ( $force_associative ) {
								return "__$k";
							}

							return $k;
						},
						$keys
					),
					$v
				);

				return array_merge_recursive( $r, $v );
			},
			[]
		);

		if ( $force_associative ) {
			$array = array_combine(
				array_map(
					function( $k ) {
						return substr( $k, 2 );
					},
					array_keys( $array )
				),
				$array
			);
		}

		return $array;
	}

	public function get_attribute_count() {
		if ( empty( $this->attributes ) ) {
			return 0;
		}

		return count( $this->attributes );
	}

	/**
	 * Prepare an associative array of attributes
	 * with the terms listed in the 'terms' property.
	 *
	 * The keys of the array are the slugs of the attributes.
	 * The terms are returned as [ id, slug, name ] arrays
	 *
	 * @since 2.0.0
	 *
	 * @param WC_Product $product The product to get the attributes for.
	 * @return array The associative array with the product attributes used for the variations
	 */
	public function get_product_attributes( $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return [];
		}

		$attributes = array_filter(
			array_map(
				function( $a ) {
					// get the taxonomy associated with the attribute name
					$tax = get_taxonomy( $a->get_name() );

					if ( $tax ) {
						// the attribute name corresponds to a taxonomy
						// retrieve the relevant term information

						// this returns the terms in the wrong order due to how WP caches the object terms
						// $terms = $a->get_terms();

						// this always bypasses the WP object cache
						$terms = get_terms( $a->get_name(), [ 'hide_empty' => true ] );
						$terms = array_map(
							function( $t ) {
								// return the term as a [ id, slug, name ] array
								return [
									'id'   => $t->term_id,
									'slug' => $t->slug,
									'name' => $t->name,
								];
							},
							$terms
						);
					} else {
						// the attribute is not a taxonomy (custom attribute)
						// the terms are listed in the 'options' array
						$terms = array_map(
							function( $o ) {
								// return the term as a [ id, slug, name ] array
								return [
									'id'   => 0,
									'slug' => $o,
									'name' => $o,
								];
							},
							$a['options']
						);
					}

					// return the relevant properties of the attribute
					// as an associative array
					return [
						'id'        => $a->get_id(),
						'name'      => $a->get_name(),
						'label'     => $tax ? $tax->labels->singular_name : $a->get_name(),
						'position'  => $a->get_position(),
						'visible'   => $a->get_visible(),
						'variation' => $a->get_variation(),
						'terms'     => $terms,
					];
				},
				$product->get_attributes()
			),
			function( $a ) {
				// filter only those attributes that are used for the variations
				return $a['variation'];
			}
		);

		// sort the attributes by the position they are listed in the product
		uasort(
			$attributes,
			function( $a, $b ) {
				return $a['position'] > $b['position'] ? 1 : -1;
			}
		);

		return $attributes;
	}

	public static function get_defaults() {

		$args = wp_parse_args( Settings::get_setting_variations_defaults(), self::$default_args );

		return $args;
	}
}
