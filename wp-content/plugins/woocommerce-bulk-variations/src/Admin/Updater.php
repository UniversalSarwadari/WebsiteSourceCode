<?php

namespace Barn2\Plugin\WC_Bulk_Variations\Admin;

use Barn2\WBV_Lib\Registerable,
	Barn2\WBV_Lib\Service,
	Barn2\Plugin\WC_Bulk_Variations\Util\Util,
	Barn2\Plugin\WC_Bulk_Variations\Util\Settings;

use const Barn2\Plugin\WC_Bulk_Variations\PLUGIN_FILE,
		  Barn2\Plugin\WC_Bulk_Variations\PLUGIN_VERSION;

/**
 * This class handles updating the DB
 *
 * @package   Barn2/woocommerce-bulk-variations
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Updater implements Registerable, Service {

	/**
	 * The path of the HTML views used for the notices
	 *
	 * @var string
	 */
	private $view_path;

	public function __construct() {
		$this->view_path = plugin_dir_path( PLUGIN_FILE ) . 'src/Admin/views/';
	}

	/**
	 * Register hooks and filters.
	 */
	public function register() {
		add_action( 'admin_notices', [ $this, 'update_notices' ] );
		add_action( 'admin_init', [ $this, 'update_action' ] );
		add_action( 'admin_init', [ $this, 'dismiss_success_notice_action' ] );
	}

	/**
	 * If we need to update the database, include a message with the DB update button.
	 */
	public function update_notices() {
		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';
		$show_on_screens = [
			'dashboard',
			'plugins',
		];

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Notices should only show on WooCommerce screens, the main dashboard, and on the plugins screen.
		if ( ! in_array( $screen_id, wc_get_screen_ids(), true ) && ! in_array( $screen_id, $show_on_screens, true ) ) {
			return;
		}

		$db_version = Util::get_db_version();
		$db_updated = get_option( 'wcbv_db_updated_200' );

		if ( $db_version === '1.0' && version_compare( PLUGIN_VERSION, '2.0-RC.1', '>=' ) ) {
			include "{$this->view_path}html-notice-update.php";
		}

		if ( $db_updated ) {
			include "{$this->view_path}html-notice-updated.php";
		}
	}

	/**
	 * Handles the update action for the admin notice
	 */
	public function update_action() {
		if ( ! empty( $_GET['do_update_woocommerce_bulk_variations'] ) ) { // WPCS: input var ok.
			$db_version = Util::get_db_version();
			check_admin_referer( 'wcbv_db_update', 'wcbv_db_update_nonce' );

			$success = false;

			if ( $db_version === '1.0' && version_compare( PLUGIN_VERSION, '2.0-RC.1', '>=' ) ) {
				$success = $this->update_200_product_override();

				if ( $success ) {
					update_option( 'wcbv_db_version', '2.0' );
					update_option( 'wcbv_db_updated_200', true );
				}
			}
		}
	}

	/**
	 * Handles the dismiss action for the upgrade success admin notice
	 */
	public function dismiss_success_notice_action() {
		if ( ! empty( $_GET['do_dismiss_wcbv_db_update_success'] ) ) {
			check_admin_referer( 'wcbv_db_success_dismiss', 'wcbv_db_success_dismiss_nonce' );

			delete_option( 'wcbv_db_updated_200' );
		}
	}

	/**
	 * Converts all product
	 *
	 * @return bool Whether the upgrade was processed successfully
	 */
	private function update_200_product_override() {
		global $wpdb;

		$errors = 0;

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE 
					{$wpdb->postmeta}
					SET meta_key = %s
					WHERE meta_key = %s",
				Settings::OPTION_VARIATIONS_DATA . '_enable',
				Settings::OPTION_VARIATIONS_DATA . '_override'
			)
		);

		$errors += false === $result;

		$result  = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
					SELECT post_id, %s, '1'
						FROM {$wpdb->postmeta}
						WHERE meta_key = %s;",
				Settings::OPTION_VARIATIONS_DATA . '_override',
				Settings::OPTION_VARIATIONS_DATA . '_enable'
			)
		);
		$errors += false === $result;

		$image_data_replacements = [
			[
				'replace' => 's:16:"variation_images";s:1:"0";',
				'with'    => 's:16:"variation_images";s:3:"off";',
			],
			[
				'replace' => 's:16:"variation_images";s:1:"1";',
				'with'    => 's:16:"variation_images";s:3:"row";',
			],
		];

		foreach ( $image_data_replacements as $img_rep ) {
			$result = $wpdb->query(
				$wpdb->prepare(
					"UPDATE 
						{$wpdb->postmeta}
						SET meta_value = REPLACE( meta_value, %s, %s )
						WHERE meta_key = %s
						AND   meta_value LIKE %s",
					$img_rep['replace'],
					$img_rep['with'],
					Settings::OPTION_VARIATIONS_DATA,
					'%' . $wpdb->esc_like( $img_rep['replace'] ) . '%'
				)
			);

			$errors += false === $result;
		}

		if ( 0 === $errors ) {
			return true;
		}

		return false;
	}
}
