<?php
	if(!class_exists('morningtrain_updateChecker')) {
		class morningtrain_updateChecker {
			private $api_url = 'http://repository.morningtrain.dk/api/generic/';
			private $plugin_slug = '';
			private $version = '';
			private $plugin_basename = '';
			private $key = '';

			public function initialize($slug, $key = "") {
				$this->plugin_slug = $slug;
				$this->key = $key;
				$this->plugin_basename = $this->plugin_slug . '/' . $this->plugin_slug . '.php';

				if(!function_exists('get_plugins')) {
					require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				}
				$plugin_folder = get_plugins();
				$this->version = $plugin_folder[$this->plugin_basename]['Version'];
				add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_plugin_update'));
				add_filter('plugins_api', array($this, 'plugin_api_call'), 10, 3);
			}

			public function check_for_plugin_update($checked_data) {
				global $wp_version;

				if(!isset($wp_version)) {
					$wp_version = '';
				}

				$args = array(
					'slug'    => $this->plugin_slug,
					'version' => $this->version,
				);

				$request_string = array(
					'body'       => array(
						'action'   => 'basic_check',
						'request'  => serialize($args),
						'api-key'  => md5(get_bloginfo('url')),
						'auth_key' => $this->key
					),
					'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
				);

				// Start checking for an update
				$raw_response = wp_remote_post($this->api_url, $request_string);
				if(!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200)) {
					$response = unserialize(base64_decode($raw_response['body']));
				}
				if(is_object($response) && !empty($response)) { // Feed the update data into WP updater
					$checked_data->response[$this->plugin_basename] = $response;
				}

				return $checked_data;
			}

			public function plugin_api_call($def, $action, $args) {
				global $wp_version;

				if(!isset($args->slug) || $args->slug !== 'woo-advanced-qty') {
					return FALSE;
				}

				// Get the current version
				$plugin_info = get_site_transient('update_plugins');
				$current_version = $plugin_info->checked[$this->plugin_basename];
				$args->version = $this->version;

				$request_string = array(
					'body'       => array(
						'action'   => $action,
						'request'  => serialize($args),
						'api-key'  => md5(get_bloginfo('url')),
						'auth_key' => $this->key
					),
					'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
				);

				$request = wp_remote_post($this->api_url, $request_string);

				if(is_wp_error($request)) {
					$res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>'), $request->get_error_message());
				} else {
					$res = unserialize(base64_decode($request['body']));
					if($res === FALSE) {
						$res = new WP_Error('plugins_api_failed', __('An unknown error occurred'), $request['body']);
					}
				}

				return $res;
			}
		}
	}