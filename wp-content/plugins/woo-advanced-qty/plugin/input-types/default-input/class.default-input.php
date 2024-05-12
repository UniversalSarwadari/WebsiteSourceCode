<?php namespace Morningtrain\WooAdvancedQTY\Plugin\InputTypes\DefaultInput;

use Morningtrain\WooAdvancedQTY\Plugin\InputTypes\InputType;

class DefaultInput extends InputType {

	protected static $slug = 'default-input';
	protected static $folder_name = 'default-input';
	protected static $template_name = null;
	protected static $priority = 1;

	public static function getName() {
		return __('Default', 'woo-advanced-qty');
	}

	public static function getInputFieldTemplate($args) {
		return null;
	}
}