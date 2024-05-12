<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class StepIntervalsController extends Controller {

	protected function registerFilters() {
		parent::registerFilters();

		Loader::addFilter('morningtrain/woo-advanced-qty/input-args', static::class, 'applyStepIntervalArgs', 70, 3);
		Loader::addFilter('morningtrain/woo-advanced-qty/quantity/parseQuantity/step_args', static::class, 'parseQuantityArgs', 10, 2);
		Loader::addFilter('morningtrain/woo-advanced-qty/quantity/getValidQuantityList/values', static::class, 'getValidQuantityValues', 10, 2);
	}

	/**
	 * Modify step_args for parse quantity if step_intervals is applied
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $args
	 * @param $quantity
	 *
	 * @return mixed
	 */
	public static function parseQuantityArgs($args, $quantity) {
		if(!array_key_exists('step_intervals', $args) || empty($args['step_intervals'])) {
			return $args;
		}

		foreach($args['step_intervals'] as $interval) {
			if($quantity >= $interval[0] && $quantity <= $interval[1]) {
				$args['min_value'] = $interval[0];
				$args['max_value'] = $interval[1];
				$args['step'] = $interval[2];
			}
		}

		return $args;
	}

	/**
	 * Get list of valid quantities (with step intervals applied)
	 * @param $values
	 * @param $args
	 *
	 * @return array
	 */
	public static function getValidQuantityValues($values, $args) {
		if(!array_key_exists('step_intervals', $args) || empty($args['step_intervals'])) {
			return $values;
		}

		$values = array();
		$_value = $args['min_value'];

		if(!isset($args['max_value']) || !is_numeric($args['max_value']) || $args['max_value'] < 0) {
			$max_count = apply_filters('morningtrain/woo-advanced-qty/quantity/getValidQuantityList/max', 100, $args);
			while(count($values) <= $max_count) {
				foreach($args['step_intervals'] as $interval) {
					if($_value >= $interval[0] && $_value < $interval[1]) {
						if($interval[0] > $args['min_value']) {
							$_value = $interval[0];
						} else {
							$_value = $args['min_value'];
						}

						while($_value < $interval[1] && count($values) <= $max_count) {
							$values[(string) QuantityController::formatQuantity($_value)] = QuantityController::formatQuantity($_value);
							$_value = $_value + $interval[2];
						}
						continue(2);
					}
				}

				$values[(string) QuantityController::formatQuantity($_value)] = QuantityController::formatQuantity($_value);

				$_value = $_value + $args['step'];
			}
		} else {
			while($_value <= $args['max_value']) {
				foreach($args['step_intervals'] as $interval) {
					if($_value >= $interval[0] && $_value < $interval[1]) {
						if($interval[0] > $args['min_value']) {
							$_value = $interval[0];
						} else {
							$_value = $args['min_value'];
						}

						while($_value < $interval[1] && $_value <= $args['max_value']) {
							$values[(string) QuantityController::formatQuantity($_value)] = QuantityController::formatQuantity($_value, 'STRING');
							$_value = $_value + $interval[2];
						}
						continue(2);
					}
				}

				$values[(string) QuantityController::formatQuantity($_value)] = QuantityController::formatQuantity($_value, 'STRING');

				$_value = $_value + $args['step'];
			}
		}

		return $values;
	}

	/**
	 * Apply step intervals args to input args
	 *
	 * @since 3.0.0 Initial added
	 *
	 * @param $args
	 * @param $product
	 *
	 * @return mixed
	 */
	public static function applyStepIntervalArgs($args, $product, $cart_input = false) {
		if(!array_key_exists('input_type', $args) || $args['input_type'] == 'default-input') {
			return $args;
		}

		$args['step_intervals'] = SettingsController::getAppliedSettingForProduct($product->get_id(), 'step-intervals');

		// if has step intervals and it is cart and it is not a cart input, modify each interval
		if(!empty($args['step_intervals']) && !$cart_input && CartController::isProductInCart($product->get_id())) {
			$in_cart_count = CartController::getProductQuantityInCart($product->get_id());
			foreach($args['step_intervals'] as &$interval) {
				$interval[0] = $interval[0] - $in_cart_count;
				$interval[1] = $interval[1] - $in_cart_count;
			}
		}

		return $args;
	}

	/**
	 * Making the array into a readable string so we can display it
	 *
	 * @since 2.0.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $step_intervals
	 *
	 * @return string
	 */
	public static function convertArrayToString($step_intervals) {
		if(empty($step_intervals)) {
			return '';
		}

		$items = array();

		foreach($step_intervals AS &$item) {
			$items[] = implode(',', $item);
		}

		return implode('|', $items);
	}

	/**
	 * Making readable stirng into the right array format
	 *
	 * @since 2.0.0 Initial added
	 * @since 3.0.0 Moved to this class
	 *
	 * @param $step_intervals
	 *
	 * @return array
	 */
	public static function convertStringToArray($step_intervals) {
		$string = trim($step_intervals);
		$step_intervals = explode('|', $string);

		foreach($step_intervals AS &$item) {
			$item = explode(',', trim($item));
			foreach($item as &$sub_item) {
				$sub_item = trim($sub_item);
			}
		}

		return $step_intervals;
	}
}