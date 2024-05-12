<?php namespace Morningtrain\WooAdvancedQTY\Plugin\InputTypes\GlobalInput;

use Morningtrain\WooAdvancedQTY\Plugin\InputTypes\InputType;

class GlobalInput extends InputType {

	protected static $slug = 'global-input';
	protected static $folder_name = 'global-input';
	protected static $priority = 0;

	public static function getName() {
		return __('Follow parent setting', 'woo-advanced-qty');
	}
}