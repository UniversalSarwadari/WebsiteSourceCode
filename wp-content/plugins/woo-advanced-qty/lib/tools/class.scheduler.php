<?php namespace Morningtrain\WooAdvancedQTY\Lib\Tools;

use Morningtrain\WooAdvancedQTY\Lib\Traits\Scheduling;

/**
 * Class Scheduler
 * A class that implements the Scheduling trait
 * Used for creating wrapper methods in Loader
 * @package MTTWordPressTheme\Lib\Tools
 */
class Scheduler
{
	use Scheduling;

	public static function register()
	{
		static::registerScheduleHooks();
	}
}

Scheduler::register();
