<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Cassandra\Set;
use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class PaypalController extends Controller {

    protected function registerFilters() {
        Loader::addFilter('woocommerce_paypal_args', static::class, 'paypalArgs', 10, 2);
    }

    /**
     * Fix PayPal Decimal quantity issue
     *
     * @param $args
     * @param $order
     *
     * @return mixed
     * @since 1.3.1
     *
     */
    public static function paypalArgs($args, $order) {
        $order_items = $order->get_items();

        foreach ($args as $key => $arg) {
            // if not a quantity arg, continue
        	if (false === strpos($key, "quantity_")) {
                continue;
            }

        	// get index (remove quantity_)
            $index = substr($key, 9);

            $product_id = null;
            $quantity_suffix = null;

            foreach($order_items as $item) {
            	if($args["item_name_$index"] == $item->get_name()) {
            		$product_id = $item->get_product_id();
            		$args['quantity_' . $index] = QuantityController::formatQuantity($item->get_quantity());
            		break;
	            }
            }

            if($product_id > 0) {
	            $quantity_suffix = SettingsController::getAppliedSettingForProduct($product_id, 'quantity-suffix');
            }

            if(!is_int($args['quantity_' . $index])) {
                $args['amount_' . $index] = round($args['amount_' . $index] * $args['quantity_' . $index], 2);
                $args['item_name_' . $index] = static::getNewProductName($args, $index, $quantity_suffix);
                $args['quantity_' . $index] = 1;
            }
        }

        return $args;
    }

    /**
     * Get new product name for PayPal (modified with quantity with float)
     *
     * @since 2.1.0 Initial added
     * @since 3.0.0 Moved to this class
     *
     * @param $args            array
     * @param $index           integer
     * @param $quantity_suffix string
     *
     * @return string
     */
    public static function getNewProductName($args, $index, $quantity_suffix = null) {
        $name = $args['item_name_' . $index] . ' x ' . $args['quantity_' . $index];

    	if(!empty($quantity_suffix)) {
            return "$name $quantity_suffix";
        }

        return $name;
    }
}