<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class MigrationsController extends Controller {

	/**
	 * Array og migrations function to call based on version
	 * @var \array[][]
	 */
	protected static $migrations = array(
		'3.0.0' => array(
			array(__CLASS__, 'convertVariationSettings_3_0_0')
		)
	);

	protected function registerActionsAdmin() {
		parent::registerActions();

		Loader::addAction('admin_init', static::class, 'maybeDoMigration');
	}

	/**
	 * If we have not done a migration for this version, then do it
	 *
	 * @since 3.0.0 Initial added
	 */
	public static function maybeDoMigration() {
		if(version_compare(Plugin::getVersion(), get_option('woo_advanced_qty_migration_version', '0.0.0'), '>')) {
			static::migrate();
		}
	}

	/**
	 * Run migration functions based on version
	 *
	 * @since 3.0.0 Initial added
	 */
	protected static function migrate() {
		$last_migration_version = get_option('woo_advanced_qty_migration_version', '0.0.0');

		foreach(static::$migrations as $version => $migration) {
			if(version_compare($version, $last_migration_version, '>')) {
				foreach($migration as $callback) {
					if(is_callable($callback)) {
						call_user_func($callback);
					}
				}
			}
		}

		update_option('woo_advanced_qty_migration_version', Plugin::getVersion());
	}

	/**
	 * Convert Variation Settings v. 3.0.0
	 *
	 * @since 3.0.0 Initial added
	 */
	public static function convertVariationSettings_3_0_0() {
		$variations = wc_get_products(array(
			'type' => 'variation',
			'limit' => -1,
		));

		if(empty($variations)) {
			return;
		}

		foreach($variations as $variation) {
			if(get_post_meta($variation->get_parent_id(), '_advanced-qty-individually-variation', true) == 1) {
				update_post_meta($variation->get_id(), ProductVariationSettingsController::getSettingName('individually_variation_control'), true);
			}
		}
	}
}