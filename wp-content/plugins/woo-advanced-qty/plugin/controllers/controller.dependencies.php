<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class DependenciesController extends Controller {

	protected function registerActionsAdmin() {
		parent::registerActionsAdmin();

		// Only display dependency errors on plugins page
		if(!empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'plugins.php') {
			Loader::addAction('admin_notices', static::class, 'addDependenciesNotice');
		}
	}

	/**
	 * Display plugin requirement errors
	 *
	 * @since 2.4.0 Initial added
	 * @since 3.0.0 Moved to this class
	 */
	public static function addDependenciesNotice() {
		// Get plugin requirements
		$errors = static::checkPluginRequirements();

		// Remove empty values
		$errors = array_filter($errors);

		// Check if no errors
		if(empty($errors)) {
			return;
		}

		Plugin::getTemplate('partials/admin-notice', array(
			'text' => sprintf('<p><strong><i>%1$s</i></strong></p><p>%2$s</p>', Plugin::getData('Name'), join('<p></p>', $errors))
		));
	}

	/**
	 * Check the plugin requirements
	 *
	 * @since 2.4.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @return array
	 */
	public static function checkPluginRequirements() {
		$errors[] = array();

		// Check if WooCommerce is installed and activated
		if(!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			$errors['woocommerce_error'] = sprintf(__('Requires WooCommerce to be installed and activated. You can download %s here.', 'woo-advanced-qty'), '<a href="https://woocommerce.com/">WooCommerce</a>');
		}

		return $errors;
	}
}