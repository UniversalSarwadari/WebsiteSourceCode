<?php namespace Morningtrain\WooAdvancedQTY\Plugin\InputTypes\PlusMinusInput;

use Morningtrain\WooAdvancedQTY\Plugin\InputTypes\InputType;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class PlusMinusInput extends InputType {

	protected static $slug = 'plus-minus-input';
	protected static $folder_name = 'plus-minus-input';
	protected static $template_name = 'plus-minus-input';
	protected static $priority = 3;

	public static function getName() {
		return __('+/-', 'woo-advanced-qty');
	}

	public static function registerScripts() {
		parent::registerScripts();

		Plugin::addScript('plus-minus-input', array('jquery'), true, 'plugin/input-types/plus-minus-input/assets');
	}

	public static function registerStyles() {
		parent::registerStyles();

		Plugin::addStyle('plus-minus-input', array(), 'all', 'plugin/input-types/plus-minus-input/assets');
	}
}