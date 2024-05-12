<?php namespace Morningtrain\WooAdvancedQTY\Plugin\Modules\PluginUpdater\Controllers;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Controller;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Plugin\Plugin;

class PluginUpdatesController extends Controller {
	static $plugin_info_url = 'https://plugins.morningtrain.dk/mtt-plugin-repository/5f86b938a4eab/plugin-info/';

	protected function registerFiltersAdmin() {
		parent::registerFiltersAdmin();

		Loader::addFilter('plugins_api', static::class, 'getPluginInfo', 10, 3);

		Loader::addFilter('pre_set_site_transient_update_plugins', static::class, 'setUpdatePluginsTransient');
	}

	protected function registerActionsAdmin() {
		parent::registerActionsAdmin();

		Loader::addAction('upgrader_process_complete', static::class, 'deleteTransientAfterUpdate', 10, 2);
	}


	public static function deleteTransientAfterUpdate($upgrader_object, $options ) {
		if( $options['action'] == 'update' && $options['type'] === 'plugin' )  {
			delete_transient( Plugin::getTextDomain() . '_plugin_info_json');
		}
	}

	/**
	 * Get plugin info from remote
	 *
	 * @param $res
	 * @param $action
	 * @param $args
	 *
	 * @return object|null
	 */
	public static function getPluginInfo($res, $action, $args) {
		if($action !== 'plugin_information') {
			return $res;
		}

		if(!isset($args->slug) || $args->slug !== Plugin::getTextDomain()) {
			return $res;
		}

		$res = static::getPluginRemote();

		if(isset($res->sections)) {
			foreach($res->sections as &$section) {
				$section = html_entity_decode($section);
			}
		}

		$res->slug = Plugin::getTextDomain();
		$res->plugin = Plugin::getBaseName();

		return $res;
	}

	/**
	 * Set plugin data to update transient
	 * @param $transient
	 *
	 * @return mixed
	 */
	public static function setUpdatePluginsTransient($transient) {
		if(!isset($transient->last_checked)) {
			return $transient;
		}

		$plugin_remote = static::getPluginRemote($transient->last_checked);

		if(empty($plugin_remote)) {
			return $transient;
		}

		if(version_compare(Plugin::getVersion(), $plugin_remote->version, '>=') || version_compare($plugin_remote->requires, get_bloginfo('version'), '>')) {
			return $transient;
		}

		$res = new \stdClass();
		$res->slug = Plugin::getTextDomain();
		$res->plugin = Plugin::getBaseName();
		$res->new_version = $plugin_remote->version;
		$res->tested = $plugin_remote->tested;
		$res->package = $plugin_remote->download_link;
		$transient->response[Plugin::getBaseName()] = $res;

		return $transient;
	}

	/**
	 * Get active plugins for API call
	 * @return array
	 */
	public static function extractActivePlugins() {
		$_active_plugins = get_option('active_plugins', array());

		if(empty($_active_plugins)) {
			return array();
		}
		$_all_plugins = get_plugins();
		$active_plugins = array();

		foreach($_active_plugins as $_active_plugin) {
			if(isset($_all_plugins[$_active_plugin])) {
				$active_plugins[$_active_plugin] = array(
					'name' => $_all_plugins[$_active_plugin]['Name'],
					'plugin_uri' => $_all_plugins[$_active_plugin]['PluginURI'],
					'version' => $_all_plugins[$_active_plugin]['Version'],
				);
			}
		}

		return $active_plugins;
	}

	/**
	 * Get active them for API call
	 * @return array
	 */
	public static function extractActiveTheme() {
		$_theme = wp_get_theme();

		$theme = array(
			'name' => $_theme->name,
			'title' => $_theme->title,
			'version' => $_theme->version,
		);

		$_parent_theme = $_theme->parent();

		if($_parent_theme) {
			$theme['parent_theme'] = array(
				'name' => $_parent_theme->name,
				'title' => $_parent_theme->title,
				'version' => $_parent_theme->version,
			);
		}

		return $theme;
	}

	/**
	 * Get Plugin info from remote
	 * @param null $last_checked
	 *
	 * @return object|null
	 */
	public static function getPluginRemote($last_checked = null) {
		$transient = get_transient(Plugin::getTextDomain() . '_plugin_info');

		if(false == $transient || ($last_checked !== null && (!isset($transient['last_check']) || $last_checked > $transient['last_check']))) {
			$transient = array(
				'last_check' => time()
			);

			$plugin_remote = wp_remote_post(static::$plugin_info_url, array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
				'body' => array(
					'wp_version' => get_bloginfo('version'),
					'website' => get_bloginfo('url'),
					'plugin_version' => Plugin::getVersion(),
					'active_plugins' => json_encode(static::extractActivePlugins()),
					'active_theme' => json_encode(static::extractActiveTheme()),
				)
			));

			if(is_wp_error($plugin_remote) || wp_remote_retrieve_response_code($plugin_remote) != 200 || empty(wp_remote_retrieve_body($plugin_remote))) {
				return null;
			}

			$transient['info'] = wp_remote_retrieve_body($plugin_remote);

			set_transient(Plugin::getTextDomain() . '_plugin_info', $transient, 43200);
		}

		return (object) json_decode($transient['info'], true); // Decode as array an converte to project to have a std class with arrays inside
	}

	/**
	 * Get last check for info from the remote
	 * @return mixed|null
	 */
	public static function getLastPluginRemoteCheck() {
		$transient = get_transient(Plugin::getTextDomain() . '_plugin_info');

		if(!isset($transient['last_check'])) {
			return null;
		}

		return $transient['last_check'];
	}
}