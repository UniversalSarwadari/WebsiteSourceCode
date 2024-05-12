<?php namespace Morningtrain\WooAdvancedQTY\Plugin\InputTypes;

use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

abstract class InputType {

	protected static $template_name = 'input';

	protected static $slug = '';

	protected static $folder_name = '';

	protected static $instance = null;

	protected static $priority = 0;

	/**
	 * Get slug
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @return string
	 */
	public static function getSlug() {
		return static::$slug;
	}

	/**
	 * Get folder name
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @return string
	 */
	public static function getFolderName() {
		return static::$folder_name;
	}

	/**
	 * Get name - Should be overwritten by children
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @return mixed
	 */
	public static function getName() {
		return static::getSlug();
	}

	/**
	 * Get template name
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @return string
	 */
	public static function getTemplateName() {
		return static::$template_name;
	}

	/**
	 * get priority for displaying
	 *
	 * @return int
	 */
	public static function getPriority() {
		return static::$priority;
	}

	/**
	 * Register scripts
	 *
	 * @since 3.0.0 Initial added
	 */
	public static function registerScripts() {

	}

	/**
	 * Register styles
	 *
	 * @since 3.0.0 Initial added
	 */
	public static function registerStyles() {

	}

	/**
	 * Apply extra args to args array (for template)
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function applyArgs($args, $product_id) {
		return $args;
	}

	/**
	 * Get input field template
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @return string
	 */
	public static function getInputFieldTemplate($args) {
		if(static::getTemplateName() == null) {
			return null;
		}
		return Plugin::locateFile(Plugin::SingleEnding(static::getTemplateName(), '.php'), array('plugin/input-types/'. static::getFolderName() . '/templates'));
	}
}