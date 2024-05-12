<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class CategorySettingsController extends Controller {

	protected function registerActionsAdmin() {
		parent::registerActionsAdmin();

		Loader::addAction('product_cat_add_form_fields', static::class, 'displayFormFieldsOnAdd', 99);
		Loader::addAction('product_cat_edit_form_fields', static::class, 'displayFormFieldsOnEdit', 99);

		Loader::addAction('created_product_cat', static::class, 'saveCategorySettings');
		Loader::addAction('edited_product_cat', static::class, 'saveCategorySettings');
	}

	/**
	 * Show options on every single product category when adding new
	 *
	 * @since 1.1.0 Initial added
	 * @since 3.0.0 Moved to this class and moved output to template
	 *
	 */
	public static function displayFormFieldsOnAdd() {
		Plugin::getTemplate('settings/category-settings-add');
	}

	/**
	 * Show options on every single product category when editing
	 *
	 * @since 1.1.0 Initial added
	 * @since 3.0.0 Moved to this class and moved output to template
	 *
	 * @param $tag
	 */
	public static function displayFormFieldsOnEdit($tag) {
		Plugin::getTemplate('settings/category-settings-edit', array(
			'term_id' => $tag->term_id
		));
	}

	/**
	 * Save category options when saving category
	 *
	 * @since 1.1.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $term_id
	 */
	public static function saveCategorySettings($term_id) {
		// check if save shold be done
		if(empty($term_id) || // term id not set
			(isset($_POST['action']) && $_POST['action'] == 'inline-save-tax') // is inline edit
		) {
			return;
		}

		// Min Quantity
		if(isset($_REQUEST['advanced-qty-min']) && $_REQUEST['advanced-qty-min'] > 0) {
			update_option('product-category-advanced-qty-min-' . $term_id, $_REQUEST['advanced-qty-min']);
		} else {
			delete_option('product-category-advanced-qty-min-' . $term_id);
		}

		// Quantity Step
		if(isset($_REQUEST['advanced-qty-step']) && $_REQUEST['advanced-qty-step'] > 0) {
			update_option('product-category-advanced-qty-step-' . $term_id, $_REQUEST['advanced-qty-step']);
		} else {
			delete_option('product-category-advanced-qty-step-' . $term_id);
		}

		// Step intervals
		if(isset($_REQUEST['advanced-qty-step-intervals']) && !empty($_REQUEST['advanced-qty-step-intervals'])) {
			$step_intervals = StepIntervalsController::convertStringToArray($_REQUEST['advanced-qty-step-intervals']);
			update_option('product-category-advanced-qty-step-intervals-' . $term_id, $step_intervals);
		} else {
			delete_option('product-category-advanced-qty-step-intervals-' . $term_id);
		}

		// Quantity Max
		if(isset($_REQUEST['advanced-qty-max']) && $_REQUEST['advanced-qty-max'] > 0) {
			update_option('product-category-advanced-qty-max-' . $term_id, $_REQUEST['advanced-qty-max']);
		} else {
			delete_option('product-category-advanced-qty-max-' . $term_id);
		}

		// Quantity Value
		if(isset($_REQUEST['advanced-qty-value']) && $_REQUEST['advanced-qty-value'] > 0) {
			update_option('product-category-advanced-qty-value-' . $term_id, $_REQUEST['advanced-qty-value']);
		} else {
			delete_option('product-category-advanced-qty-value-' . $term_id);
		}

		// Price Suffix
		if(isset($_REQUEST['advanced-qty-price-suffix']) && !empty($_REQUEST['advanced-qty-price-suffix'])) {
			update_option('product-category-advanced-qty-price-suffix-' . $term_id, $_REQUEST['advanced-qty-price-suffix']);
		} else {
			delete_option('product-category-advanced-qty-price-suffix-' . $term_id);
		}

		// Quantity Suffix
		if(isset($_REQUEST['advanced-qty-quantity-suffix']) && !empty($_REQUEST['advanced-qty-quantity-suffix'])) {
			update_option('product-category-advanced-qty-quantity-suffix-' . $term_id, $_REQUEST['advanced-qty-quantity-suffix']);
		} else {
			delete_option('product-category-advanced-qty-quantity-suffix-' . $term_id);
		}

		// Price Factor
		if(isset($_REQUEST['advanced-qty-price-factor']) && $_REQUEST['advanced-qty-price-factor'] > 0) {
			update_option('product-category-advanced-qty-price-factor-' . $term_id, $_REQUEST['advanced-qty-price-factor']);
		} else {
			delete_option('product-category-advanced-qty-price-factor-' . $term_id);
		}

		// Quantity Input Picker
		if(isset($_REQUEST['advanced-qty-input-picker']) && !empty($_REQUEST['advanced-qty-input-picker'])) {
			update_option('product-category-advanced-qty-input-picker-' . $term_id, $_REQUEST['advanced-qty-input-picker']);
		} else {
			delete_option('product-category-advanced-qty-input-picker-' . $term_id);
		}
	}

	/**
	 * Get setting name based on an identifier (ex. min, step or max)
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $identifier
	 *
	 * @return string
	 */
	protected static function getSettingName($identifier, $term_id) {
		$prefix = 'product-category-advanced-qty-';

		return $prefix . $identifier . '-' . $term_id;
	}

	/**
	 * Get setting for category based on a identifier (ex. min, step or max)
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param      $term_id
	 * @param      $identifier
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public static function getSetting($term_id, $identifier, $default = null) {
		$setting = get_option(static::getSettingName($identifier, $term_id));

		if(empty($setting)) {
			$setting = $default;
		}

		return apply_filters('morningtrain/woo-advanced-qty/settings/category/getSetting', $setting, $identifier, $default);
	}
}