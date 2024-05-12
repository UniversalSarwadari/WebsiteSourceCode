<?php namespace Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes;

use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class AdminPageFactory {

	protected static $admin_pages = [];

	public function __construct() {
		$this->registerHooks();
	}

	private function registerHooks() {
		Loader::addAction('admin_menu', $this, 'registerMenuPages');
	}

	public function registerMenuPages() {
		foreach(static::$admin_pages as $page) {
			$page->registerMenuPage();
		}
	}

	/* STATIC FUNCTIONS */
	public static function addMenuPage($title, $slug, $args = []) {
		$defaults = array(
			'page_title' => $title,
			'menu_title' => $title,
			'submenu_title' => $title,
			'menu_slug' => $slug,
			'type' => 'top-menu',
		);

		$args = \wp_parse_args($args, $defaults);

		static::$admin_pages[$slug] = new AdminPage($args);

		return static::$admin_pages[$slug];
	}

	public static function addSubmenuPage($title, $parent_slug, $slug, $args = []) {
		$defaults = array(
			'parent_slug' => $parent_slug,
			'page_title' => $title,
			'menu_title' => $title,
			'menu_slug' => $slug,
			'type' => 'sub-menu',
		);

		$args = \wp_parse_args($args, $defaults);

		static::$admin_pages[$slug] = new AdminPage($args);

		return static::$admin_pages[$slug];
	}

	public static function addOptionsPage($title, $slug, $args = []) {
		$defaults = array(
			'parent_slug' => 'options-general.php',
		);

		$args = \wp_parse_args($args, $defaults);

		return static::addSubmenuPage($title, $args['parent_slug'], $slug, $args);
	}
}