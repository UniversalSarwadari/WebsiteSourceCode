<?php namespace Morningtrain\WooAdvancedQTY\Lib\Abstracts;

abstract class CLICommand {

	/**
	 * The command to call Eks. 'mtt queueworker start'
	 * @var string
	 */
	protected static $command = '';

	public static function register() {
		// Do not load if CLI is not running
		if(!defined('WP_CLI') || !WP_CLI || !class_exists('WP_CLI')) {
			return;
		}

		\WP_CLI::add_command(static::$command, static::class);
	}
}