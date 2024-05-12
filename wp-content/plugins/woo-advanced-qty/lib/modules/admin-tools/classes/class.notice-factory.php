<?php namespace Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Project;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class NoticeFactory {

	protected static $notices = array();
	protected static $notices_to_save = array();

	public function __construct() {
		$this->registerHooks();
		$this->loadNotices();
	}

	private function registerHooks() {
		Loader::addAction('admin_notices', $this, 'displayNotices');
		Loader::addAction('admin_enqueue_scripts', $this, 'addScript');
	}

	public function addScript() {
        if(!empty(static::$notices)) {
			Project::addScript('notices.js', array('jquery'), true, array('lib.modules.admin-tools.assets.js'));
		}
	}

	private function loadNotices() {
		static::$notices = static::$notices_to_save = \get_option(Project::getTextDomain() . '_notices', array());
		foreach(static::$notices as $notice) {
			Loader::addAction('wp_ajax_dismiss_notice_' . $notice['id'], __CLASS__, 'dismissNotice');
		}
	}

	public function displayNotices() {
		foreach(static::$notices as $notice) {
			ob_flush(); // Fixes error where it will not display notice after a redirect
			if(isset($notice['remove_after'])){
				if($notice['remove_after'] < 1) {
					$this->removeNotice($notice['id']);
					continue;
				} else {
					$this->removeNoticeAfter($notice['id'], --$notice['remove_after']);
				}
			}
			Project::getTemplate('partials.notice', array('notice' => $notice), true, array('lib.modules.admin-tools.templates'));
		}
	}

	public static function dismissNotice() {
		static::removeNotice($_GET['message']);
		\wp_die();
	}

	public static function saveNotices() {
		\update_option(Project::getTextDomain() . '_notices', static::$notices_to_save);
	}

	public static function addNotice($class, $message, $id = '', $remove_notice_after = 1) {
		$notice_id = $id !== '' ? $id : uniqid();

		static::$notices[$notice_id] = array(
			'id' => $notice_id,
			'class' => $class,
			'message' => $message,
			'is_dismissible' => is_bool($remove_notice_after) ? $remove_notice_after : false,
		);

		if(is_int($remove_notice_after)) {
			static::$notices[$notice_id]['remove_after'] = $remove_notice_after;
		}

		static::$notices_to_save[$notice_id] = static::$notices[$notice_id];
		static::saveNotices();
	}

	public static function addNoticeSuccess($message, $id = '', $remove_notice_after = 1) {
		static::addNotice('notice-success', $message, $id, $remove_notice_after);
	}

	public static function addNoticeError($message, $id = '', $remove_notice_after = 1) {
		static::addNotice('notice-error', $message, $id, $remove_notice_after);
	}

	public static function addNoticeWarning($message, $id = '', $remove_notice_after = 1) {
		static::addNotice('notice-warning', $message, $id, $remove_notice_after);
	}

	public static function addNoticeInfo($message, $id = '', $remove_notice_after = 1) {
		static::addNotice('notice-info', $message, $id, $remove_notice_after);
	}

	public static function removeNotice($id, $before_request = true) {
		if($before_request) {
			unset(static::$notices[$id]);
		}
		if(isset(static::$notices_to_save[$id])) {
			unset(static::$notices_to_save[$id]);
		}
		static::saveNotices();
	}

	public static function removeNoticeAfter($id, $requests = 1) {
		static::$notices_to_save[$id]['remove_after'] = $requests;
		static::saveNotices();
	}
}