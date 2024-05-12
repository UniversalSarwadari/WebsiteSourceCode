<?php namespace Morningtrain\WooAdvancedQTY\Plugin\InputTypes\Dropdown;

use Morningtrain\WooAdvancedQTY\Plugin\InputTypes\InputType;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class Dropdown extends InputType {

	protected static $slug = 'dropdown';
	protected static $folder_name = 'dropdown';
	protected static $template_name = 'dropdown';
	protected static $priority = 2;

	public static function getName() {
		return __('Dropdown', 'woo-advanced-qty');
	}

	public static function registerScripts() {
		parent::registerScripts();
		Plugin::addScript('dropdown', array('jquery'), true, 'plugin/input-types/dropdown/assets');
	}
}