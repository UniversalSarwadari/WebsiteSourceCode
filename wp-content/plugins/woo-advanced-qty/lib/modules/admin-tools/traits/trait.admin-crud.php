<?php namespace Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Traits;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Model;
use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Project;
use Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes\AdminPageFactory;
use Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes\AdminTable;
use Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes\NoticeFactory;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;

/**
 * Trait ModelAdminPage
 * @package MTTPlugin\Lib\Traits
 */
trait AdminCRUD {

	/**
	 * Args for CRUD (Model Admin Page)
	 * @var array
	 */
	protected static $admin_crud_args = array();

	/**
	 * Entry / Init method. Should be called in parent constructor
	 */
	private static function registerAdminCRUD($args = array()) {
		static::registerACFDataFilters();
		// Bail early if not in admin area
		if (!is_admin()) return;

		static::setAdminCRUDProperties($args);
		static::registerAdminCRUDActions();
		static::registerAdminCRUDFilters();
	}

	/**
	 * Set properties for the admin CRUD
	 * @param array $args
	 *
	 * @throws \ReflectionException
	 */
	private static function setAdminCRUDProperties($args = array()) {
		// Set label defaults and merge with labels in args if exists
		$label_defaults = array(
			'menu_page_name' => ucfirst(static::getSlug()),
			'add_new_instance' => Project::translateReplace('Tilføj %s', ucfirst(static::getSlug())),
			'edit_instance' => Project::translateReplace('Edit %s', ucfirst(static::getSlug())),
			'delete_button' => Project::translate('Slet'),
			'save_button' => Project::translate('Opdatér'),
			'publish_button' => Project::translate('Udgiv'),
			'table_search' => Project::translate('Søg'),
		);

		if(isset($args['labels'])) {
			$label_defaults = wp_parse_args($args['labels'], $label_defaults);
			unset($args['labels']);
		}

		// Set default args
		$defaults = array(
			'icon_url' => 'dashicons-admin-generic',
			'labels' => $label_defaults,
			'menu_page_slug' => static::getSlug(),
			'position' => 35
		);

		static::$admin_crud_args = wp_parse_args($args, $defaults);

		if(!isset(static::$admin_crud_args['menu_page_edit_slug'])) {
			static::$admin_crud_args['menu_page_edit_slug'] = 'edit_' . static::$admin_crud_args['menu_page_slug'];
		}
	}

	/**
	 * Get a Admin CRUD property
	 * @param $property_name
	 * @param $default
	 *
	 * @return mixed
	 */
	public static function getAdminCRUDProperty($property_name, $default = null) {
		if(isset(static::$admin_crud_args[$property_name])) {
			return static::$admin_crud_args[$property_name];
		}

		return $default;
	}

	/**
	 * Get a admin CRUD label
	 * @param        $label_name
	 * @param string $default
	 *
	 * @return string
	 */
	public static function getAdminCRUDLabel($label_name, $default = '') {
		$labels = static::getAdminCRUDProperty('labels');

		if(isset($labels[$label_name])) {
			return $labels[$label_name];
		}

		return $default;
	}

	/**
	 * Register Trait Actions
	 */
	protected static function registerAdminCRUDActions() {
		// Setup pages
		Loader::addAction('init', static::class, 'setupAdminCRUDPages');

		// Page Load
		Loader::addAction('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_slug') . '/load', static::class, 'adminCRUDListPageLoad');
		if(static::getAdminCRUDProperty('menu_page_edit_slug')) {
			Loader::addAction('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_edit_slug') . '/load', static::class, 'adminCRUDEditPageLoad', -10);
		}

		// Page Content
		Loader::addAction('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_slug') . '/content', static::class, 'adminCRUDListPageContent');
		if(static::getAdminCRUDProperty('menu_page_edit_slug')) {
			Loader::addAction('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_edit_slug') . '/content', static::class, 'adminCRUDEditPageContent');
		}

		// Meta boxes
		Loader::addAction('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_slug') . '/load', static::class, 'addMetaBoxes');
		if(static::getAdminCRUDProperty('menu_page_edit_slug')) {
			Loader::addAction('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_edit_slug') . '/load', static::class, 'addMetaBoxes');
		}

		// Save instance
		Loader::addAction(static::getHookName('admin-page', 'save'), static::class, 'adminCRUDSave');

		// Enqueue Scripts
		Loader::addAction('admin_enqueue_scripts', static::class, 'adminCRUDEnqueueAdminStyles');
		Loader::addAction('admin_enqueue_scripts', static::class, 'adminCRUDEnqueueAdminScripts');
		Loader::addAction('admin_enqueue_scripts',  static::class, 'enqueueMetaBoxScripts'); // Add Metabox functionality to edit page

		Loader::addAction(static::getHookName('admin-page', 'action', 'delete'), static::class, 'onDelete');

		Loader::addAction(static::getHookName("admin","table","display","column","default"), static::class,'getDefaultColumnData', 10, 2);

		$table_slug = \apply_filters(static::getHookName('get', 'table', 'slug'), static::getSlug());

		Loader::addAction('mtt/admin/page/table/' . $table_slug . '/before', static::class, 'displaySearchBox');
		Loader::addAction($table_slug . '_total_items', static::class, 'totalItems');
	}

	/**
	 * Enqueue metabox scripts
	 */
	static function enqueueMetaBoxScripts() {
		if(static::isAdminCRUDEditPage()){
			wp_enqueue_script( 'post');
		}
	}

	/**
	 * Register Trait Filters
	 */
	protected static function registerAdminCRUDFilters() {
		Loader::addAction('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_slug') . '/actions', static::class, 'addAdminCRUDPageActions');
		Loader::addFilter('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_slug') . '/title', static::class, 'adminCRUDChangePageTitle');
		Loader::addFilter('mtt/admin/page/' . static::getAdminCRUDProperty('menu_page_edit_slug') . '/title', static::class, 'adminCRUDChangePageTitle');

		// ACF Location rules
		Loader::addFilter('acf/location/rule_types', static::class, 'acfLocationRuleTypes');
		Loader::addFilter('acf/location/rule_values/mtt_model', static::class, 'acfLocationRuleValues');
		Loader::addFilter('acf/location/rule_match/mtt_model', static::class, 'acfLocationRuleMatch', 10, 3);

		Loader::addFilter('mtt/admin/page/acf/model/' . static::getSlug() . '/search_columns', static::class, 'acfModelRelationSearchColumns');
		Loader::addFilter('mtt/admin/page/acf/model/' . static::getSlug() . '/select_title', static::class, 'acfModelRelationTitle', 10, 2);

		Loader::addAction('wp_ajax_admin-crud/acf/validate_fields', static::class, 'doACFValidation');
	}

	public static function registerACFDataFilters()
	{
		// ACF SAVE AND GET
		Loader::addFilter('acf/pre_update_metadata', static::class, 'updateCorrectACFData', 10, 5);
		Loader::addFilter('acf/pre_load_metadata', static::class, 'getCorrectACFData', 10, 4);
		Loader::addFilter('acf/pre_load_post_id', static::class, 'getCorrectACFPostID', 10, 2);
	}

	public static function doACFValidation()
	{
		acf_validate_save_post();

		$errors = acf_get_validation_errors();

		wp_send_json_success(['errors' => $errors], 200);
	}

	/**
	 * Get URL to a crud site
	 *
	 * @param string $page list_page, edit_page is supported
	 * @param array  $args
	 *
	 * @return string
	 */
	public static function getAdminCRUDURL($page = 'list_page', $args = array()) {
		switch($page) {
			case 'edit_page':
				$slug = static::getAdminCRUDProperty('menu_page_edit_slug');

				if($slug === static::getAdminCRUDProperty('menu_page_slug')) {
					$args['action'] = 'add_new';
				}

				break;
			default:
				$slug = static::getAdminCRUDProperty('menu_page_slug');
		}

		return add_query_arg($args, html_entity_decode(menu_page_url($slug, false)));
	}

	/**
	 * Add page action
	 * @param array $actions
	 *
	 * @return array|mixed|void
	 * @throws \ReflectionException
	 */
	public static function addAdminCRUDPageActions($actions = array()) {
		if(!static::isAdminCRUDListPage()) {
			return $actions;
		}
		$default_actions = array();

		if(!empty(static::getAdminCRUDURL('edit_page'))) {
			$default_actions['add_new'] = array(
				'label' => static::getAdminCRUDLabel('add_new_instance'),
				'classes' => array('page-title-action'),
				'href' =>  static::getAdminCRUDURL('edit_page'),
			);
		}

		return apply_filters(static::getHookName('get','page','actions'), $default_actions);
	}

	/**
	 * Gets called when the listpage gets loaded.
	 *
	 * This page displays a table displaying rows from model in memberListPageContent()
	 * This function also handles save / deletion of rows
	 *
	 * @see static::memberListPageContent()
	 * @throws \ReflectionException
	 */
	public static function adminCRUDListPageLoad() {
		if(isset($_GET['action'])){
			/**
			 * Page action			 *
			 * Eg. "mtt/MTTPluginName/modelslug/page/action/trash"
			 *
			 * @param string $instance
			 */
			do_action(static::getHookName('admin-page', 'action', $_GET['action']), $_GET); //  Used to be ''mtt/admin/page/members/delete'
		}

		if(!self::isAdminCRUDListPage()) {
			return;
		}

		$table_name_single = \apply_filters(static::getHookName('get', 'table', 'name', 'single'), static::getSlug());
		$table_name_plural = \apply_filters(static::getHookName('get', 'table', 'name', 'plural'), $table_name_single . 's');
		$table_slug = \apply_filters(static::getHookName('get', 'table', 'slug'), static::getSlug());

		$admin_table_args = \apply_filters(static::getHookName('get', 'table', 'args'), array(
			'items_callback' => array(__CLASS__, 'getRowsForAdminTable')
		));

		$table = new AdminTable($table_slug, $table_name_plural, $table_name_single, $admin_table_args);

		$table_columns = apply_filters(static::getHookName('get', 'admin', 'table', 'columns'), static::getAdminCRUDProperty('table_columns', array()));

		$table->addColumns($table_columns);

		$row_actions = array(
			'edit' => array(
				'action' => 'add_new',
				'title' => Project::translate('Edit'),
				'args' => array(
					'page' => static::getAdminCRUDProperty('menu_page_edit_slug'),
					'template' => 'partials.table.action',
					'slug' => static::getSlug(),
				),
			),
			'trash' => array(
				'action' => 'delete',
				'title' => Project::translate('Delete'),
				'args' => array(
					'page' => static::getAdminCRUDProperty('menu_page_slug'),
					'class' => 'delete',
					'template' => 'partials.table.action',
					'slug' => static::getSlug(),
				),
			),
		);
		if(empty(static::getAdminCRUDProperty('menu_page_edit_slug'))) {
			unset($row_actions['edit']);
		}

		/**
		 * Lets you add or remove action on each row in the table
		 *
		 * @see \MTTPlugin\Lib\Tools\Admin\AdminTable::addRowAction()
		 *
		 * @param array $row_actions
		 */
		$row_actions = apply_filters(static::getHookName('get', 'table', 'row', 'actions'), $row_actions);

		foreach ($row_actions as $row_action) {
			$action = (!empty($row_action['action'])) ? $row_action['action'] : '';
			$table->addRowAction($action, $row_action['title'], $row_action['args']);
		}

		$table->prepare_items();
	}

	/**
	 * Change page title based on action
	 * @param $page_title
	 *
	 * @return string|void
	 */
	public static function adminCRUDChangePageTitle($page_title) {

		$current_instance = static::getGlobalInstance();

		if( static::isAdminCRUDEditPage() && ($current_instance === null || $current_instance->getID() === 0)){
			return static::getAdminCRUDLabel('add_new_instance');
		}

		if(static::isAdminCRUDEditPage()) {
			$page_title = static::getAdminCRUDLabel('edit_instance');
		}

		return $page_title;
	}

	public static function adminCRUDEnqueueAdminStyles() {
		Project::addStyle('admin-style');
	}

	/**
	 * Enqueue acf scripts
	 */
	public static function adminCRUDEnqueueAdminScripts() {
		if(function_exists('acf_is_screen') && acf_is_screen(static::getAdminScreenID(static::getAdminCRUDProperty('menu_page_edit_slug')))) {
			acf_enqueue_scripts();
		}
	}

	/**
	 * Prepares the admin pages
	 * @throws \ReflectionException
	 */
	public static function setupAdminCRUDPages() {
		// Setup list page
		if(!empty(static::getAdminCRUDProperty('menu_page_slug'))) {
			if(is_numeric(static::getAdminCRUDProperty('position'))) {
				AdminPageFactory::addMenuPage(static::getAdminCRUDLabel('menu_page_name'), static::getAdminCRUDProperty('menu_page_slug'), static::$admin_crud_args);
			} else {
				AdminPageFactory::addSubmenuPage(static::getAdminCRUDLabel('menu_page_name'), static::getAdminCRUDProperty('position'), static::getAdminCRUDProperty('menu_page_slug'), static::$admin_crud_args);
			}
		}

		// Setup edit page
		if(!empty(static::getAdminCRUDProperty('menu_page_edit_slug')) && static::getAdminCRUDProperty('menu_page_edit_slug') != static::getAdminCRUDProperty('menu_page_slug')) {
			$page = AdminPageFactory::addSubmenuPage(static::getAdminCRUDLabel('add_new_instance'), (is_numeric(static::getAdminCRUDProperty('position')) ? static::getAdminCRUDProperty('menu_page_slug') : static::getAdminCRUDProperty('position')), static::getAdminCRUDProperty('menu_page_edit_slug'), static::$admin_crud_args);
		}
	}

	/**
	 * Get Admin Screen ID for specific page
	 * @param $slug
	 */
	public static function getAdminScreenID($slug) {
		global $_parent_pages;
		$parent = isset($_parent_pages[$slug]) ? $_parent_pages[$slug] : '';
		return get_plugin_page_hookname($slug, $parent);
	}

	/**
	 * Return true if current screen is admin CRUD list page
	 * @return bool
	 */
	public static function isAdminCRUDListPage() {
		$screen = \get_current_screen();
		return ($screen->id == static::getAdminScreenID(static::getAdminCRUDProperty('menu_page_slug')) && (!isset($_GET['action']) || $_GET['action'] != 'add_new'));
	}

	/**
	 * Is current page the admin edit page?
	 * @return bool
	 */
	public static function isAdminCRUDEditPage()
	{
		$screen = \get_current_screen();

		return (($screen->id == static::getAdminScreenID(static::getAdminCRUDProperty('menu_page_edit_slug')) && static::getAdminCRUDProperty('menu_page_edit_slug') != static::getAdminCRUDProperty('menu_page_slug')) || (isset($_GET['action']) && $_GET['action'] == 'add_new'));
	}

	/**
	 * Returns the current instance if currently on one of the admin screens
	 *
	 * @return static|null
	 * @throws \ReflectionException
	 */
	public static function getGlobalInstance()
	{
		$var = 'mtt_' . static::getSlug() . '_instance';
		global ${$var};
		if (static::isA( ${$var} )) return ${$var};
		if( static::isAdminCRUDEditPage() ) {
			if(!empty($_GET) && !empty($_GET[static::getSlug()])){
				${$var} = new static( (int) $_GET[static::getSlug()]);
			}else{
				${$var} = new static();
			}
			return ${$var};
		}
		return NULL;
	}



	/**
	 * The content for the admin page where rows are listed!
	 * @throws \ReflectionException
	 */
	public static function adminCRUDListPageContent() {
		if(!self::isAdminCRUDListPage()) {
			return;
		}
		$args = array(
			'menu_page_slug' => static::getAdminCRUDProperty('menu_page_slug'),
			'table_slug' => \apply_filters(static::getHookName('get', 'table', 'slug'), static::getSlug()),
		);

		Project::getTemplate('pages.table', $args, true, array('lib.templates', 'lib.modules.admin-tools.templates'));
	}

	/**
	 * Outputs the name for the AdminTable column
	 * @param self::class $item
	 */
	public static function displayNameColumn($item){
		echo $item->first_name.' '.$item->last_name;
	}

	/** The default way to render cell data
	 * @param $item
	 * @param $column
	 */
	public static function getDefaultColumnData($item, $column){
		$column_settings = static::getTableColumnSettings($column);
		if(isset($column_settings['edit_link']) && $column_settings['edit_link']) {
			if(is_string($column_settings['edit_link'])) {
				$edit_link = $column_settings['edit_link'];
			} else {
				$edit_link = static::getAdminCRUDURL('edit_page', array(static::getSlug() => $item->getPrimaryKey()));
			}
		} else {
			$edit_link = false;
		}

		$text = isset($item->$column) ? $item->$column: '';


		Project::getTemplate('partials.table.column-display', array('item' => $item, 'column' => $column, 'edit_url' => $edit_link, 'text' => $text), true, array('lib.modules.admin-tools.templates'));
	}

	/**
	 * Return table column settings;
	 * @param $column_name
	 *
	 * @return |null
	 */
	public static function getTableColumnSettings($column_name) {
		$table_columns = static::getAdminCRUDProperty('table_columns');

		if(isset($table_columns[$column_name])) {
			return $table_columns[$column_name];
		}

		return null;
	}

	/** Fetches the rows for the table
	 * @param $array
	 * @param $page
	 * @param $items_per_page
	 * @return array
	 */
	public static function getRowsForAdminTable($array, $page, $items_per_page) {
		$args = array(
			'posts_per_page' => $items_per_page,
			'page' => $page,
		);
		if(isset($_GET['orderby'])){
			$args['orderby'] = $_GET['orderby'];
		} else if(static::getAdminCRUDProperty('table_init_orderby') !== null) {
			$args['orderby'] = static::getAdminCRUDProperty('table_init_orderby');
		}
		if(isset($_GET['order'])){
			$args['order'] = $_GET['order'];
		} else if(static::getAdminCRUDProperty('table_init_order') !== null) {
			$args['order'] = static::getAdminCRUDProperty('table_init_order');
		}
		if(isset($_GET['filterby']) && isset($_GET['filter'])){
			$args['where'][] = "{$_GET['filterby']} LIKE '%{$_GET['filter']}%'";
		}
		if(isset($_GET['s']) && !empty(static::getAdminCRUDProperty('table_search'))) {
			$search_args = array(
				'relation' => 'OR',
			);
			foreach(static::getAdminCRUDProperty('table_search') as $column_name) {
				$search_args[] = "$column_name LIKE '%{$_GET['s']}%'";
			}
			$args['where'][] = $search_args;
		}

		$args = apply_filters(static::getFilterName('admin', 'page', 'table', 'items', 'args'), $args);

		return static::get($args);
	}

	/**
	 * Edit page load
	 * @throws \ReflectionException
	 */
	public static function adminCRUDEditPageLoad() {
		if(!self::isAdminCRUDEditPage()) {
			return;
		}
		if(!empty($_POST) && isset($_POST['save_'.static::getSlug()])) {
			$instance = static::getGlobalInstance();
			do_action(static::getHookName('admin-page', 'save'), $instance);
			if($_POST[static::getSlug()]['id'] !== $instance->getPrimaryKey()){
				\wp_redirect(static::getAdminCRUDURL('edit_page', array(static::getSlug() => $instance->getPrimaryKey())));
			}
		}
	}

	/**
	 * Putputs the edit form and more on the edit page
	 *
	 * There are metabox sections for you to hook into here : )
	 *
	 * @throws \ReflectionException
	 */
	public static function adminCRUDEditPageContent() {
		if(!self::isAdminCRUDEditPage()) {
			return;
		}
		$slug = static::getSlug();

		$instance = static::getGlobalInstance();

		// Lets ACF know our content type
		Loader::addFilter('acf/get_post_id_info', static::class, 'acfSetPostIdInfo', 10, 2);
		Project::getTemplate('pages.edit', array(
			'instance'          => $instance,
			'type'              => $slug,
		), true, array('lib.templates', 'lib.modules.admin-tools.templates'));
		remove_filter('acf/get_post_id_info', static::class, 'acfSetPostIdInfo', 10, 2);
	}


	/**
	 * Adds metaboxes
	 * @throws \ReflectionException
	 */
	public static function addMetaBoxes( ){
		// EDIT PAGE
		if(static::isAdminCRUDEditPage()) {
			add_meta_box('submitdiv', Project::translate('Status'), array(static::class, 'addSubmitMetaBox'), static::getAdminScreenID(static::getAdminCRUDProperty('menu_page_edit_slug')), 'side', 'high');

			static::addACFMetaBoxes();
		}


		do_action(static::getHookName('admin-page','metaboxes'), static::getGlobalInstance(), static::getAdminScreenID(static::getAdminCRUDProperty('menu_page_edit_slug')));
	}

	public static function addSubmitMetaBox() {
		$instance = static::getGlobalInstance();
		$id = ( !empty($instance) && !empty($instance->getPrimaryKey())) ? $instance->getPrimaryKey() : '';

		if($id < 1) {
			$save_label = static::getAdminCRUDLabel('publish_button');
		} else {
			$save_label = static::getAdminCRUDLabel('save_button');
		}

		Project::getTemplate('partials.submitbox', array(
			'delete_url' => static::getAdminCRUDURL('list_page', array('action' => 'delete', $instance::getSlug() => $instance->getPrimaryKey())),
			'delete_label' => static::getAdminCRUDLabel('delete_button'),
			'id' => $id,
			'slug' => static::getSlug(),
			'save_label' => $save_label,
			'instance' => $instance,
		), true, array('templates', 'lib/modules/admin-tools/templates'));
	}

	public static function addACFMetaBoxes() {
		if(!static::isAdminCRUDEditPage()) {
			return;
		}

		Loader::addFilter('acf/get_post_id_info', static::class, 'acfSetPostIdInfo', 10, 2);
		$field_groups = acf_get_field_groups(['mtt_model' => static::getSlug()]);

		if (empty($field_groups)) {
			return;
		}

		foreach ($field_groups as $field_group) {
			// vars
			$id = "acf-{$field_group['key']}";
			$title = $field_group['title'];
			$context = $field_group['position'];
			$priority = 'high';
			$args = array( 'field_group' => $field_group );


			// tweaks to vars
			if( $context == 'acf_after_title' ) {

				$context = 'normal';

			} elseif( $context == 'side' ) {

				$priority = 'core';

			}

			// add meta box
			add_meta_box( $id, $title, array(static::class, 'renderACF'), static::getAdminScreenID(static::getAdminCRUDProperty('menu_page_edit_slug')), $context, $priority, $args );

		}
		remove_filter('acf/get_post_id_info', static::class, 'acfSetPostIdInfo', 10, 2);
	}

	public static function renderACF($post, $args) {
		Loader::addFilter('acf/get_post_id_info', static::class, 'acfSetPostIdInfo', 10, 2);
		// extract args
		extract( $args ); // all variables from the add_meta_box function
		extract( $args ); // all variables from the args argument

		// vars
		$o = array(
			'id'			=> $id,
			'key'			=> $field_group['key'],
			'style'			=> $field_group['style'],
			'label'			=> $field_group['label_placement'],
			'editLink'		=> '',
			'editTitle'		=> __('Edit field group', 'acf'),
			'visibility'	=> true
		);

		// edit_url
		if( $field_group['ID'] && acf_current_user_can_admin() ) {

			$o['editLink'] = admin_url('post.php?post=' . $field_group['ID'] . '&action=edit');

		}

		// load fields
		$fields = acf_get_fields( $field_group );

		// render
		acf_render_fields( $fields, 'model_' . $post->getSlug() . '_' . $post->getPrimaryKey(), 'div', $field_group['instruction_placement'] );

		?>
		<script type="text/javascript">
			if( typeof acf !== 'undefined' ) {

				acf.newPostbox(<?php echo json_encode($o); ?>);

			}
		</script>
		<?php
		remove_filter('acf/get_post_id_info', static::class, 'acfSetPostIdInfo', 10, 2);
	}

	// ACF

	/**
	 * Adding Morning train rule group with option Model to ACF
	 *
	 * @see https://www.advancedcustomfields.com/resources/custom-location-rules/#adding-a%20new%20rule
	 * @param array $rules
	 * @return array $rules
	 */
	public static function acfLocationRuleTypes($rules) {
		if( empty($rules['Morning Train']['mtt_model']) ){
			$rules['Morning Train']['mtt_model'] = Project::translate('Model');
		}
		return $rules;
	}

	/**
	 * Add this model to the Mtt Model group
	 *
	 * @param $choices
	 * @return mixed choices
	 * @throws \ReflectionException
	 */
	public static function acfLocationRuleValues($choices) {
		$choices[static::getSlug()] = static::getAdminCRUDLabel('menu_page_name');
		return $choices;
	}

	/**
	 * Determines whether a field group should be displayed on a screen or not.
	 *
	 * @see https://www.advancedcustomfields.com/resources/custom-location-rules/#adding-a%20new%20rule under "4. Mathcing the rule"
	 *
	 * @param bool $match       Whether the group should be rendered or not
	 * @param array $rule       The current rule to match against
	 * @param array $options    Info about the current screen
	 *
	 * @return bool $match
	 *
	 * @throws \ReflectionException
	 */
	public static function acfLocationRuleMatch($match, $rule, $options) {
		if(acf_is_screen(static::getAdminScreenID(static::getAdminCRUDProperty('menu_page_edit_slug')))) {
			if($rule['operator'] == '==' && $rule['value'] == static::getSlug()) {
				$match = true;
			}elseif ($rule['operator'] == '!=' && $rule['value'] != static::getSlug()){
				$match = true;
			}
		}
		return $match;
	}


	/**
	 * Saves acf stuff if possible
	 *
	 * @param array $columns
	 * @param $instance
	 * @throws \ReflectionException
	 */
	public static function acfSave($instance )
	{
		// Upon saving we need to let ACF know that the current content type is our model
		if(!static::isA($instance)){
			$instance = static::getGlobalInstance();
		}

		if($instance === null){
			return;
		}

		if( !empty($_POST['acf'])){
			acf_save_post('model_' . static::getSlug() . '_' . $instance->getPrimaryKey());
		}
	}

	/**
	 * Delete instance
	 *
	 * @param $item
	 */
	public static function onDelete($item) {
		$id = $item[static::getSlug()];
		$result = static::deleteByID($id);

		if(is_wp_error($result)) {
			NoticeFactory::addNoticeError('Something went wrong');
		} else if(!empty($result)) {
			NoticeFactory::addNoticeSuccess(Project::translateReplace('Deleted successfully'));
		}
	}

	/**
	 * Lets ACF know that the current data type is our model so that it updates metadata correctly
	 *
	 * @param $info
	 * @param $id
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public static function acfSetPostIdInfo($info, $id) {
		$info['type'] = static::getSlug();
		return $info;
	}

	/**
	 * Save model instance
	 * @param $instance static
	 */
	public static function adminCRUDSave($instance) {
		// Check nonce
		check_admin_referer('edit_' . static::getSlug(), '_nonce_' . static::getSlug());

		$instance = apply_filters(static::getFilterName('admin-page', 'before-save', 'instance'), $instance);

		// Handle other inputs
		$instance = apply_filters(static::getFilterName('admin-page', 'save', 'instance'), $instance);

		static::acfSave($instance);

		$result = $instance->save();

		if($result) {
			NoticeFactory::addNoticeSuccess(Project::translate('Successfully saved'));
		} else {
			NoticeFactory::addNoticeError(Project::translate('Something went Wrong'));
		}
	}

	/**
	 * Display Search box
	 */
	public static function displaySearchBox($slug) {
		if(!empty(static::getAdminCRUDProperty('table_search'))) {
			AdminTable::displayTableSearch($slug, static::getAdminCRUDLabel('table_search'));
		}
	}

	/**
	 * Total items for admin table pagination
	 * @param $total_items
	 *
	 * @return int
	 */
	public static function totalItems($total_items) {
		return static::getTotalCount();
	}

	/**
	 * Searchable columns for the ACF Model relation field
	 * @param $columns
	 *
	 * @return mixed
	 */
	public static function acfModelRelationSearchColumns($columns) {
		$model_relation_settings = static::getAdminCRUDProperty('acf_model_relation', false);

		if(!empty($model_relation_settings['search_columns'])) {
			$columns = $model_relation_settings['search_columns'];
		}

		return $columns;
	}

	/**
	 * Title oneach instance on ACF Model input
	 *
	 * @param string                                 $title
	 * @param \MTTWordPressTheme\Lib\Abstracts\Model $instance
	 *
	 * @return string
	 */
	public static function acfModelRelationTitle($title, Model $instance) {
		$model_relation_settings = static::getAdminCRUDProperty('acf_model_relation', false);

		if(!empty($model_relation_settings['display_column'])) {

			if(is_array($model_relation_settings['display_column'])) {
				$title = array();
				foreach($model_relation_settings['display_column'] as $column) {
					$title[] = $instance->{$column};
				};
				$title = implode(' - ', $title);
			} else {
				$title = $instance->{$model_relation_settings['display_column']};
			}
		}

		return $title;
	}

	/**
	 * Set up a list table for this Model
	 *
	 * Used for showing a list of elements outside of ListPage context
	 *
	 * Provide a callback to determine which items should be included in the table, default static::getAll()
	 *
	 * @param null $item_callback default [static::class, 'getAll']
	 *
	 * @throws \ReflectionException
	 */
	public static function setupListTable($item_callback = null)
	{
		$table_name_single = \apply_filters(static::getHookName('get', 'table', 'name', 'single'), static::getSlug());
		$table_name_plural = \apply_filters(static::getHookName('get', 'table', 'name', 'plural'), $table_name_single . 's');
		$table_slug = \apply_filters(static::getHookName('get', 'table', 'slug'), static::getSlug());

		Loader::addFilter($table_name_plural . '_before_add_column', static::class, 'removeSortableColumns');

		$table = new AdminTable($table_slug, $table_name_plural, $table_name_single, array(
			'items_callback' => $item_callback ?? [static::class, 'getAll'],
			'use_screen_settings' => false,
		));

		$table_columns = apply_filters(static::getHookName('get', 'admin', 'table', 'columns'), static::getAdminCRUDProperty('table_columns', array()));

		$table->addColumns($table_columns);
		$table->prepare_items();
	}

	/**
	 * Remove sortable columns from table when used inside another model CRUD
	 *
	 * @param $column
	 *
	 * @return mixed
	 */
	public static function removeSortableColumns($column)
	{
		$column['sortable'] = false;
		return $column;
	}

	/**
	 * Get ACF Data From correct DB Table
	 * @param $value
	 * @param $post_id
	 * @param $name
	 * @param $hidden
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	public static function getCorrectACFData($value, $post_id, $name, $hidden) {
		$parts = explode('_', $post_id);

		if(count($parts) === 3 && $parts[0] === 'model' && $parts[1] === static::getSlug()) {
			$model = static::getByPrimaryKey($parts[2], [],false);

			$prefix = $hidden ? '_' : '';

			if(isset($model->{$prefix . $name})) {
				return $model->{$prefix . $name};
			}

			return '__return_null';
		}

		return $value;
	}

	/**
	 * Get the proper Post ID for admin crud model data
	 *
	 * @param $return
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public static function getCorrectACFPostID($return, $post_id) {
		$parts = explode('_', $post_id);

		if(count($parts) === 3 && $parts[0] === 'model' && $parts[1] === static::getSlug()) {
			return $post_id;
		}

		return $return;
	}

	/**
	 * Save ACF Data in correct DB table (set it on the global instance, which is saved later)
	 * @param $return
	 * @param $post_id
	 * @param $name
	 * @param $value
	 * @param $hidden
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public static function updateCorrectACFData($return, $post_id, $name, $value, $hidden) {
		$parts = explode('_', $post_id);

		if(count($parts) === 3 && $parts[0] === 'model' && $parts[1] === static::getSlug()) {
			$current_instance = static::getGlobalInstance();

			// Hidden meta uses an underscore prefix.
			$prefix = $hidden ? '_' : '';

			$current_instance->{$prefix . $name} =  $value;

			return true;
		}

		return $return;
	}
}
