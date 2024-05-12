<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;

class SettingsController extends Controller {

	/**
	 * Get option by identifier
	 *
	 * Settings applied to products overrides global & category settings
	 * and settings applied to categories overrides global settings.
	 * If the product has more than one category, the first category with the setting is used.
	 *
	 * @since 2.0.0 Initial added
	 * @since 3.0.0 Moved to this class and rewritten - Now first category with the setting is used, instead of the last
	 *
	 * @param int    $product_id ID of product.
	 * @param string $identifier Option identifier, ending of the option key.
	 * @param null   $default    Optional. Default return value.
	 * @param string $ignore     Values to ignore
	 *
	 * @return mixed|null|string
	 */
	public static function getAppliedSettingForProduct($product_id, $setting_identifier, $default = null, $ignore = array('global-input')) {
		// Shortcurcit setting, if returned from filter
		$setting = apply_filters('morningtrain/woo-advanced-qty/settings/getAppliedSettingForProduct', null, $product_id, $setting_identifier, $default, $ignore);
		if(!empty($setting) && !in_array($setting, $ignore)) {
			return $setting;
		}


		// if setting is applied for product, then use it
		$product_setting = ProductSettingsController::getSetting($product_id, $setting_identifier);
		if(!empty($product_setting) && !in_array($product_setting, $ignore)) {
			return $product_setting;
		}

		// if setting is applied for a category, then use it
		$terms = get_the_terms($product_id, 'product_cat');

		if(!empty($terms)) {
			foreach((array) $terms as $category) {
				$category_setting = CategorySettingsController::getSetting($category->term_id, $setting_identifier);

				if(!empty($category_setting) && !in_array($category_setting, $ignore)) {
					return $category_setting;
				}
			}
		}

		// if setting is applied globally, then use it
		$global_setting = GlobalSettingsController::getSetting($setting_identifier);
		if(!empty($global_setting)) {
			return $global_setting;
		}

		// Use default
		return $default;
	}
}