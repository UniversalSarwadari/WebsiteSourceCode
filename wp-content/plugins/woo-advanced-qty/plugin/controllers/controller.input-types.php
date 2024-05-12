<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class InputTypesController extends Controller {

	static $input_types = null;

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addFilter('wc_get_template', static::class, 'displayInput', 10, 5);
	}

	/**
	 * Init input types by finding input type classes in folder
	 *
	 * @since 3.0.0 Initial added
	 */
	protected static function initInputTypes() {
		$_dir = Plugin::getRoot('plugin/input-types');

		if(!is_dir($_dir)) {
			static::$input_types = array();
			return;
		}

		$dir_iterator = new \DirectoryIterator($_dir);

		foreach($dir_iterator as $folder) {
			if($folder->isDot() || !$folder->isDir()) {
				continue;
			}

			static::initInputType($folder->getFileName());
		}
	}

	/**
	 * Init a single input type
	 *
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	public static function initInputType($name) {
		$folder_path = Plugin::getRoot("plugin/input-types/{$name}");

		$file_name = "class.{$name}.php";

		$file_path = trailingslashit($folder_path) . $file_name;

		if(!file_exists($file_path)) {
			return false;
		}

		$class_name_parts = explode('-', $name);

		$class_name = '';
		foreach($class_name_parts as $part) {
			$class_name .= ucfirst($part);
		}

		$folder_namespace = Plugin::getInfo('namespace');

		$dir = Plugin::decodeFolderStructure("plugin/input-types/{$name}");

		$dir_parts = explode('/', $dir);

		foreach($dir_parts as $part) {
			$name_parts = explode('-', $part);
			$folder_namespace .= '\\';
			foreach($name_parts as $name_part) {
				$folder_namespace .= ucfirst($name_part);
			}
		}

		$class_name = $folder_namespace . '\\' . $class_name;

		if(class_exists($class_name)) {
			static::$input_types[$name] = $class_name;
		}

		uasort(static::$input_types, function($a, $b) {
			return $a::getPriority() > $b::getPriority() ? 1 : -1;
		});

		return true;
	}

	/**
	 * Get input types (instances)
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @return array
	 */
	public static function getInputTypes() {
		if(static::$input_types === null) {
			static::initInputTypes();
		}

		return static::$input_types;
	}

	/**
	 * Get input type
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public static function getInputType($name) {
		// Try to initialize if not already initialized
		if(!isset(static::$input_types[$name])) {
			static::initInputType($name);
		}

		// If initialized and exists
		if(isset(static::$input_types[$name])) {
			return static::$input_types[$name];
		}

		// Does not exits return null
		return null;
	}

	/**
	 * Gets the input types
	 *
	 * @since 2.3.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param bool $leave_out_global
	 *
	 * @return array
	 */
	public static function getInputTypesList($leave_out_global = false) {
		$input_types = static::getInputTypes();

		$input_types_list = array();

		foreach($input_types as $input_type) {
			$input_types_list[$input_type::getSlug()] = $input_type::getName();
		}

		if($leave_out_global && isset($input_types_list['global-input'])) {
			unset($input_types_list['global-input']);
		}

		return $input_types_list;
	}

	/**
	 * Overrides WooCommerces templates
	 *
	 * @since 2.0.0 Initial added
	 * @since 3.0.0 Moved to this class and made dynamic
	 *
	 * @param $located
	 * @param $template_name
	 * @param $args
	 * @param $template_path
	 * @param $default_path
	 *
	 * @return string
	 */
	public static function displayInput($located, $template_name, $args, $template_path, $default_path) {
		if($template_name !== 'global/quantity-input.php' || !isset($args['input_type'])) {
			return $located;
		}

		$input_type_class = static::getInputType($args['input_type']);

		if(empty($input_type_class)) {
			return $located;
		}

		$input_type_class::registerScripts();
		$input_type_class::registerStyles();

		$template = $input_type_class::getInputFieldTemplate($args);

		if(!empty($template)) {
			return $template;
		}

		return $located;
	}
}