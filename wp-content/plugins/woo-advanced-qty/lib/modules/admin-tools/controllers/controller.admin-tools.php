<?php namespace Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes\AdminPageFactory;
use Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes\NoticeFactory;

class AdminToolsController extends Controller {

	public function __construct() {
		parent::__construct();

		if(\is_admin()) {
			$this->init();
		}
	}

	public function init() {
		new NoticeFactory();
		new AdminPageFactory();
	}
}