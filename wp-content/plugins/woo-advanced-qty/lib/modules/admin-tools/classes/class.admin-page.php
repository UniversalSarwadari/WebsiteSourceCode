<?php namespace Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Project;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

class AdminPage {

	private $type;
	private $template;
	private $page_title;
	private $menu_title;
	private $submenu_title;
	private $capability;
	private $menu_slug;
	private $component;
	private $callback;
	private $icon_url;
	private $position;
	private $parent_slug;
	private $hook;
	private $tabs;
	private $help_tabs;
	private $help_sidebar;

	public function __construct($page_info) {
		$this->addPageInfo($page_info);

		$this->registerHooks();
	}

	public function addPageInfo($page_info = []) {
		$defaults = [
			'type' => 'top-menu',
			'template' => 'menu-page',
			'menu_title' => Project::translate('Admin Page'),
			'capability' => 'manage_options',
			'menu_slug' => 'admin-page',
			'component' => $this,
			'callback' => 'displayMenuPage',
			'icon_url' => '',
			'position' => null,
			'parent_slug' => '',
			'hook' => '',
			'tabs' => [],
		];

		$defaults['page_title'] = $defaults['menu_title'];
		$defaults['submenu_title'] = $defaults['menu_title'];

		$page_info = \wp_parse_args($page_info, $defaults);

		foreach($page_info as $key => $info) {
			if(property_exists($this, $key)) {
				$this->{$key} = $info;
			}
		}
	}

	public function registerHooks() {
        Loader::addAction('mtt/admin/page/' . $this->menu_slug . '/tabs', $this, 'displayTabs');
        Loader::addAction('mtt/admin/page/' . $this->menu_slug . '/contentcallback', $this, 'displayContent');
	}

	public function displayTabs() {
		$active_tab = $this->getActiveTab();
		if($active_tab !== '') {
			Project::getTemplate('partials.tabs', array('tabs' => $this->tabs, 'active_tab' => $active_tab, 'page' => $this, 'primary_tab' => $this->getPrimaryTab()), true, array('lib.modules.admin-tools.templates'));
		}
	}

	protected function registerHelpTabs() {
		$screen = \get_current_screen();
		if(is_array($this->help_tabs)) {
			foreach($this->help_tabs as $help_tab) {
				$screen->add_help_tab($help_tab);
			}
		}
		if($this->help_sidebar !== NULL) {
			if(is_callable($this->help_sidebar)) {
				$content = call_user_func($this->help_sidebar);
			} else {
				$content = $this->help_sidebar;
			}
			$screen->set_help_sidebar($content);
		}
	}

	public function beforePageLoad() {
		$this->registerHelpTabs();

        $active_tab = $this->getActiveTab();

        \do_action('mtt/admin/page/' . $this->menu_slug . (!empty($active_tab) ? '/tab/' . $active_tab : '') . '/load');
	}

	public function displayContent() {
        $active_tab = $this->getActiveTab();

        \do_action('mtt/admin/page/' . $this->menu_slug . (!empty($active_tab) ? '/tab/' . $active_tab : '') . '/content');
	}

	public function getPrimaryTab($prefix = '', $suffix = '') {
		$primary_tab = '';

		if(is_array($this->tabs) && count($this->tabs) > 1) {
			$primary_tab = $prefix . $this->tabs[0]['slug'] . $suffix;
		}

		return $primary_tab;
	}

	public function getActiveTab($prefix = '', $suffix = '') {
		$active_tab = '';

		if(is_array($this->tabs) && count($this->tabs) > 1) {
			$active_tab = $prefix . $this->tabs[0]['slug'] . $suffix;
			if(isset($_GET['tab'])) {
				$active_tab = $prefix . $_GET['tab'] . $suffix;
			}
		}

		return $active_tab;
	}

	public function getPageTitle() {
		return apply_filters('mtt/admin/page/' . $this->menu_slug . '/title', $this->page_title);
	}

	public function displayMenuPage() {
		Project::getTemplate($this->template, ['page' => $this], true, array('templates.admin', 'lib.modules.admin-tools.templates'));
	}

	public function registerMenuPage() {
		if($this->isTopMenu()) {
			$this->hook = \add_menu_page($this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array($this->component, $this->callback), $this->icon_url, $this->position);
			if($this->submenu_title !== $this->menu_title) {
				\add_submenu_page($this->menu_slug, $this->page_title, $this->submenu_title, $this->capability, $this->menu_slug);
			}
		} else if($this->isSubMenu()) {
			$this->hook = \add_submenu_page($this->parent_slug, $this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array($this->component, $this->callback));
		}
		Loader::addAction('load-' . $this->hook, $this, 'beforePageLoad');
	}

	public function addSubmenuPage($title, $slug, $args = []) {
		if($this->isTopMenu()) {
			return AdminPageFactory::addSubmenuPage($title, $this->menu_slug, $slug, $args);
		}
	}

	public function addHelpTab($title, $content = '', array $args = array()) {
		$defaults = array(
			'title' => $title,
			'id' => \sanitize_title($title)
		);

		if(is_callable($content)) {
			$defaults['callback'] = $content;
		} else {
			$defaults['content'] = $content;
		}

		$tab_info = \wp_parse_args($args, $defaults);

		$this->help_tabs[$tab_info['id']] = $tab_info;
	}

	public function setHelpSidebar($content) {
		$this->help_sidebar = $content;
	}

	public function isTopMenu() {
		return $this->type === 'top-menu';
	}

	public function isSubMenu() {
		return $this->type === 'sub-menu';
	}

	public function __get($name) {
		if(property_exists($this, $name)) {
			return $this->{$name};
		}
	}
	
	public function addPageTabs(array $tabs) {
		foreach($tabs as $tab) {
			$defaults = [
				'title' => $tab['title'],
				'slug' => $tab['slug'],
				'content_component' => null,
				'content_callback' => '',
			];

			$tab = \wp_parse_args($tab, $defaults);

			$this->addPageTab($tab['title'], $tab['slug'], $tab['content_component'], $tab['content_callback']);
		}
	}

	public function addPageTab($title, $slug, $content_component = null, $content_callback = '') {
		$this->tabs[] = [
			'title' => $title,
			'slug' => $slug,
		];

		if($content_callback != '') {
			\add_action($this->menu_slug . '_' . $slug . '_content', ($content_component !== null ? array($content_component, $content_callback) : $content_callback));
		}
	}

	public function addSettingsSection($id, $title, $slug = '', $args = []) {
		if(!empty($slug)) {
			$slug = $this->menu_slug . '-' . $slug;
		} else {
			$slug = $this->menu_slug;
		}

		return addSettingsSection($id, $title, $slug, $args);
	}
}