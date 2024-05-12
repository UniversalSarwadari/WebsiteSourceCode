<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;

class DevicesController extends Controller {

	/**
	 * Checks if user is mobile and if setting has been set
	 *
	 * @return bool
	 */
	public static function isMobileDevice() {
		if(!array_key_exists('HTTP_USER_AGENT', $_SERVER) || empty($_SERVER['HTTP_USER_AGENT'])) {
			// We can not check the device, so we can not tell if it is a mobile
			return false;
		}

		return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}
}