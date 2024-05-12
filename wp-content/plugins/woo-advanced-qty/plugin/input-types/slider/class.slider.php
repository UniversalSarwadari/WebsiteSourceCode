<?php namespace Morningtrain\WooAdvancedQTY\Plugin\InputTypes\Slider;

use Morningtrain\WooAdvancedQTY\Plugin\InputTypes\InputType;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class Slider extends InputType {

	protected static $slug = 'slider';
	protected static $folder_name = 'slider';
	protected static $template_name = 'slider';
	protected static $priority = 3;

	public static function getName() {
		return __('Slider', 'woo-advanced-qty');
	}

	public static function registerScripts() {
		parent::registerScripts();
		Plugin::addScript('slider', array('jquery', 'jquery-ui-slider'), true, 'plugin/input-types/slider/assets');
	}

	public static function registerStyles() {
		parent::registerStyles();

		PLugin::addStyle('https://ajax.googleapis.com/ajax/libs/jqueryui/' . $GLOBALS['wp_scripts']->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css');
		PLugin::addStyle('slider', array(), 'all', 'plugin/input-types/slider/assets');
	}
}