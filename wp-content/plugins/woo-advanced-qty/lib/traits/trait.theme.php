<?php namespace Morningtrain\WooAdvancedQTY\Lib\Traits;

trait Theme {
	/**
	 * theme constructor.
	 *
	 * @param $namespace
	 * @param $plugin_file
	 */
	public function __construct($namespace, $is_child_theme) {
		$this->setInfo('theme', $this);

		$theme_path = $is_child_theme ? get_stylesheet_directory() : get_template_directory();

		parent::__construct($namespace, $theme_path);
	}

	/**
	 * Returns name of the theme Admin Class
	 * @return string
	 */
	protected function getAdminClassName() {
		return $this->getInfo('namespace', '') . '\Theme\ThemeAdmin';
	}

	/**
	 * Reads theme data from the stylesheet file
	 * @return array
	 */
	protected function readProjectData() {
		return \wp_get_theme();
	}

	/**
	 * Regsiter classes
	 */
	protected function registerClasses() {
		$this->initClassLoader('lib');
		$this->initClassLoader('theme');
	}
}