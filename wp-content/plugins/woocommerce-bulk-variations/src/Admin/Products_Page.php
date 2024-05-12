<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Admin;

use Barn2\Plugin\WC_Bulk_Variations\Util\Util,
	Barn2\Plugin\WC_Bulk_Variations\Util\Settings,
	Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service;

use const Barn2\Plugin\WC_Bulk_Variations\PLUGIN_FILE,
		  Barn2\Plugin\WC_Bulk_Variations\PLUGIN_VERSION;

/**
 * Provides functions for the plugin settings page in the WordPress admin.
 *
 * Settings can be accessed at WooCommerce -> Settings -> Products -> Bulk Variations.
 *
 * @package   Barn2\woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Products_Page implements Registerable, Service {

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
		// Add product tabs
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_tab' ] );

		// Add bulk variation fields view
		add_action( 'woocommerce_product_data_panels', [ $this, 'add_product_view' ] );

		// Save bulk variation fields
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_fields' ], 999 );

		// Product page JS
		add_action( 'admin_enqueue_scripts', [ $this, 'product_admin_scripts' ] );

		add_action( 'admin_footer', [ $this, 'print_additional_filter_item_template' ] );
	}

	public function update_fields() {
		$override = isset( $_POST[ Settings::OPTION_VARIATIONS_DATA . '_override' ] ) ? $_POST[ Settings::OPTION_VARIATIONS_DATA . '_override' ] : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing

		return ! is_null( $override );
	}

	public function save_product_fields( $post_id ) {
		$product = wc_get_product( $post_id );

		if ( is_a( $product, 'WC_Product_Variable' ) && $this->update_fields() ) {
			// Save fields
			$override = isset( $_POST[ Settings::OPTION_VARIATIONS_DATA . '_override' ] ) ? $_POST[ Settings::OPTION_VARIATIONS_DATA . '_override' ] : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$enable   = isset( $_POST[ Settings::OPTION_VARIATIONS_DATA . '_enable' ] ) ? $_POST[ Settings::OPTION_VARIATIONS_DATA . '_enable' ] : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! is_null( $override ) ) {
				update_post_meta( $post_id, Settings::OPTION_VARIATIONS_DATA . '_override', esc_attr( $override ) );
			}

			if ( ! is_null( $enable ) ) {
				update_post_meta( $post_id, Settings::OPTION_VARIATIONS_DATA . '_enable', esc_attr( $enable ) );
			} else {
				delete_post_meta( $post_id, Settings::OPTION_VARIATIONS_DATA . '_enable' );
			}

			$data = isset( $_POST[ Settings::OPTION_VARIATIONS_DATA ] ) ? $_POST[ Settings::OPTION_VARIATIONS_DATA ] : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! is_null( $data ) && is_array( $data ) ) {
				update_post_meta( $post_id, Settings::OPTION_VARIATIONS_DATA, $data );
			}

			$structure = isset( $_POST[ Settings::OPTION_VARIATIONS_STRUCTURE ] ) ? $_POST[ Settings::OPTION_VARIATIONS_STRUCTURE ] : null; //phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! is_null( $structure ) && is_array( $structure ) ) {
				update_post_meta( $post_id, Settings::OPTION_VARIATIONS_STRUCTURE, $structure );
			}
		} else {
			delete_post_meta( $post_id, Settings::OPTION_VARIATIONS_DATA . '_override' );
			delete_post_meta( $post_id, Settings::OPTION_VARIATIONS_DATA . '_enable' );
			delete_post_meta( $post_id, Settings::OPTION_VARIATIONS_DATA );
			delete_post_meta( $post_id, Settings::OPTION_VARIATIONS_STRUCTURE );
		}
	}

	public function add_product_view() {
		include_once $this->views_path . 'html-meta-boxes-data-panel.php';
	}

	public function product_admin_scripts() {
		global $post;
		if ( $post && $post->post_type === 'product' ) {

			wp_enqueue_style( 'wc-bulk-variations-product', Util::get_asset_url( 'css/admin/wc-bulk-variations-product.min.css' ), [], PLUGIN_VERSION );
			wp_enqueue_script( 'wc-bulk-variations-product', Util::get_asset_url( 'js/admin/wc-bulk-variations-product.min.js' ), [ 'jquery' ], PLUGIN_VERSION, true );

			wp_enqueue_style( 'wc-bulk-variations-manager', Util::get_asset_url( 'css/admin/wc-bulk-variations-manager.min.css' ), [ 'woocommerce_admin_styles' ], PLUGIN_VERSION );
			wp_enqueue_script( 'wc-bulk-variations-manager', Util::get_asset_url( 'js/admin/wc-bulk-variations-manager.min.js' ), [ 'jquery' ], PLUGIN_VERSION, true );

			$global_settings = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA );

			$params = [
				'option_variation_data' => Settings::OPTION_VARIATIONS_DATA,
				'settings'              => $global_settings,
				'choose_label'          => __( 'Select attribute', 'woocommerce-bulk-variations' ),
			];

			$script = sprintf( 'const wcbvp_data = %s;', wp_json_encode( $params ) );

			wp_add_inline_script( 'wc-bulk-variations-product', $script, 'before' );

			$params = [
				// translators: %count% is the number of results in the search, %totals% is in the format 'x variations' (e.g. 72 variations)
				'filter_results'                     => __( '%count% results out of %totals%', 'woocommerce-bulk-variations' ),
				// translators: %totals% is replaced with expression 'N variations' (e.g. 72 variations)
				'filter_singular_result'             => __( '%count% result out of %totals%', 'woocommerce-bulk-variations' ),
				'bulk_actions_filtered_only'         => __( 'Apply to filtered variations only', 'woocommerce-bulk-variations' ),
				'delete_filtered_variations'         => __( 'Delete filtered variations', 'woocommerce-bulk-variations' ),
				'select_stock_status_placeholder'    => __( 'Select one or more stock status', 'woocommerce-bulk-variations' ),
				'warning_delete_filtered_variations' => __( 'Are you sure you want to delete the current selection of variations? This cannot be undone.', 'woocommerce-bulk-variations' ),
				'warning_remove_thumbnails'          => __( 'Are you sure you want to remove the thumbnails from the current selection of variations? This cannot be undone.', 'woocommerce-bulk-variations' ),
				'wp_media_title'                     => __( 'Select the variation thumbnail', 'woocommerce-bulk-variations' ),
				'wp_media_button'                    => __( 'Select', 'woocommerce-bulk-variations' ),
			];

			$script = sprintf( 'const wcbvp_manager_translations = %s;', wp_json_encode( $params ) );

			wp_add_inline_script( 'wc-bulk-variations-manager', $script, 'before' );
		}
	}

	public function print_additional_filter_item_template() {
		echo '<script type="text/html" id="tmpl-wcbvp-filter-item">';
		require_once "{$this->views_path}html-meta-boxes-additional-filter-item.php";
		echo '</script>';
	}

	public function add_product_tab( $tabs ) {
		$tabs['bulk_variations'] = [
			'label'    => 'Bulk Variations',
			'target'   => 'bulk_variations_product_data',
			'class'    => [ 'show_if_bulk_variations' ],
			'priority' => 65
		];

		return $tabs;
	}
}
