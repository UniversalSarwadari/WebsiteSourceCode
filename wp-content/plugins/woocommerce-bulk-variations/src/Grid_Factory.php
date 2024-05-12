<?php

namespace Barn2\Plugin\WC_Bulk_Variations;

/**
 * Grid_Factory class.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Grid_Factory {

	private static $grids      = [];
	private static $current_id = 1;

	/**
	 * Create a new grid based on the supplied args.
	 *
	 * @param array $args The args to use for the grid.
	 * @return Grid The product grid object.
	 */
	public static function create( $args ) {

		// Merge in the default args, so our grid ID reflects the full list of args, including settings page.
		$id = self::generate_id( $args );

		$grid               = new Grid( $id, $args );
		self::$grids[ $id ] = $grid;

		return $grid;
	}

	/**
	 * Fetch an existing grid by ID.
	 *
	 * @param string $id The product grid ID.
	 * @return Grid The product varriations grid object.
	 */
	public static function fetch( $id ) {

		if ( empty( $id ) ) {
			return false;
		}

		$grid = false;

		if ( isset( self::$grids[ $id ] ) ) {
			$grid = self::$grids[ $id ];
		}

		return $grid;
	}

	private static function generate_id( $args ) {
		$id = 'wbv_' . substr( md5( serialize( $args ) ), 0, 16 ) . '_' . self::$current_id; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		self::$current_id ++;

		return $id;
	}
}
