<?php namespace Morningtrain\WooAdvancedQTY\Plugin;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Project;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class Plugin extends Project {
	use \Morningtrain\WooAdvancedQTY\Lib\Traits\plugin;

	protected function registerActions() {
		parent::registerActions();

		Loader::addAction('wp_enqueue_scripts', static::class, 'registerStyles');

		Loader::addAction('init', static::class, 'loadPluginTextDomain');
	}

	public static function registerStyles() {
		static::addStyle('woo-advanced-qty');
	}

	public static function loadPluginTextDomain() {
		load_plugin_textdomain(static::getTextDomain(), FALSE, dirname(plugin_basename(__DIR__)) . '/languages');
	}
}