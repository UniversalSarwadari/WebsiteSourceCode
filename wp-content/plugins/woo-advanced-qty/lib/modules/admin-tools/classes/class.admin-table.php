<?php namespace Morningtrain\WooAdvancedQTY\Lib\Modules\AdminTools\Classes;

// Include WP_List_Table class
use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Project;
use Morningtrain\WooAdvancedQTY\Lib\Abstracts\ThemeAdmin;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Debug;
use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Theme\Theme;

if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * This class extends WordPress Admin Table and makes it easy to display custom tables
 *
 * @package	MorningTrainWPPluginFramework
 * @author	Morning Train <mail@morningtrain.dk>
 * @author	Martin Schadegg Rasch Jensen <ms@morningtrain.dk>
 * @since	1.0.0
 */
class AdminTable extends \WP_List_Table {

    /**
     * Contains all initialized tables
     *
     * @var     array
     *
     * @since   5.0.0
     */
    protected static $tables = array();

    /**
     * Slug identifier
     *
     * @var string
     *
     * @since 5.0.0
     */
    protected $slug = '';

    /**
     * Contains the table columns
     *
     * The columns are saved in format 'column-name' => 'Column Title'
     *
     * @var		array
     *
     * @since	1.0.0
     */
    protected $columns = array();

    /**
     * Contains the sortable table columns
     *
     * The sortable columns are saved in format' column-name' => 'Column Title'
     *
     * @var		array
     *
     * @since	1.0.0
     */
    protected $sortable_columns = array();

    /**
     * Contains callback functions for columns if exists
     *
     * The column callbacks are saved in format 'column-name' => callable CALLBACK
     *
     * @var		array
     *
     * @since	1.0.0
     */
    protected $column_callbacks = array();

    /**
     * Contains the arguments
     *
     * @var		array
     *
     * @since	1.0.0
     */
    protected $args = array();

    /**
     * Contains the row actions
     *
     * @var 	array
     *
     * @since 	1.0.0
     */
    protected $row_actions = array();

	/**
	 * Plugin admin class
	 *
	 * @var     PluginAdmin
	 *
	 * @since 2.0.3
	 */
	protected $plugin_admin = null;


    /**
     * AdminTable constructor.
     *
     * @param	string	$plural		Plural identifier
     * @param	string	$singular	Singular identifier
     * @param	array	$args		Extra arguments:
     *			                	    bool		ajax					Allow ajax
     *                      			string		screen	 				Screen ID
     *                      			callable	before_table_callback	Function to call in the header table nav
     *                      			callable	after_table_callback	Function to call in the footer table nav
     *                      			callable	items_callback			Function to call to retrieve items
     *                      			callable	per_page_callback		Function to call to retrieve number of items per page
     *                      			callable	total_items_callback	Function to call to retrieve number of total items
     *
     * @since	1.0.0
     */
    public function __construct($slug, $plural, $singular, array $args = array()) {
        $this->slug = $slug;

		$defaults = array(
			'plural' => $plural,
			'singular' => $singular,
			'ajax' => false,
			'screen' => null,
			'before_table_callback' => null,
			'after_table_callback' => null,
			'items_callback' => null,
			'per_page_callback' => null,
			'total_items_callback' => null,
			'inline_edit_callback' => null,
			'bulk_edit_callback' => null,
			'pagination' => true,
			'display_tablenav' => true,
            'use_screen_settings' => true,
		);

		$this->args = \wp_parse_args($args, $defaults);

		parent::__construct(
			array(
				'plural' => $this->args['plural'],
				'singular' => $this->args['singular'],
				'ajax' => $this->args['ajax'],
				'screen' => $this->args['screen'],
			)
		);

		if($this->args['before_table_callback'] !== null) {
			Loader::addAction($this->getTableSlug() . '_table_top_bar', null, $this->args['before_table_callback']);
		}

		if($this->args['after_table_callback'] !== null) {
			Loader::addAction($this->getTableSlug() . '_table_bottom_bar', null, $this->args['before_bottom_callback']);
		}

        if($this->args['items_callback'] !== null) {
            Loader::addFilter($this->getTableSlug() . '_get_items', null, $this->args['items_callback'], 10, 3);
        }

        if($this->args['inline_edit_callback'] !== null || $this->args['bulk_edit_callback'] !== null) {
            Theme::addScript('table-inline-edit.js');
        }

        static::$tables[$this->slug] = $this;

		$this->addScreenOption();
	}

	/**
	 * Saves per page screen option
	 *
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public static function setScreenOption($status, $option, $value) {

		if($option === 'per_page') {
			return $value;
		}

		return $status;
	}

	/**
	 * Adds a per page screen option
	 *
	 * @since	1.0.0
	 */
	public function addScreenOption() {
		if($this->args['pagination']) {
			\add_screen_option('per_page', array('option' => 'per_page'));
		}
	}

	/**
	 * Returns the plural table name
	 *
	 * @return 	string	Plural table name
	 *
	 * @since	1.0.0
	 */
	public function getTableName() {
		return $this->args['plural'];
	}

	public function getTableSlug() {
		return $this->slug;
	}

	/**
	 * Function to display extra elements in the header or footer table nav
	 *
	 * @param	string 	$which	top or bottom table nav
	 *
	 * @since	1.0.0
	 */
	public function extra_tablenav($which) {
		switch($which) {
			case 'top':
				$this->displayTableTopBar();
				break;
			case 'bottom':
				$this->displayTableBottomBar();
				break;
		}
	}

	/**
	 * Displays the table header or footer
	 *
	 * @param $which
	 *
	 * @since    1.0.0
	 */
	public function display_tablenav($which) {
		if($this->args['display_tablenav']) {
			parent::display_tablenav($which);
		}
	}

	/**
	 * Call the before_table_callback
	 *
	 * @since	1.0.0
	 */
	public function displayTableTopBar() {
		\do_action($this->getTableSlug() . '_table_top_bar', $this);
	}

	/**
	 * Call the after_table_callback
	 *
	 * @since	1.0.0
	 */
	public function displayTableBottomBar() {
		\do_action($this->getTableSlug() . '_table_bottom_bar');
	}

	/**
	 * Add a column to the table
	 *
	 * @param	string		$id				ID/name of column
	 * @param	string		$title			Title of column
	 * @param	string		$sortable_id	If set the column will be sortable
	 * @param 	callable	$callback		If set the callback will be called when displaying content of column
	 *
	 * @since	1.0.0
	 */
	public function addColumn($id, $title, $sortable_id = false, $callback = null) {
		$this->columns[$id] = $title;

		if(!empty($sortable_id)) {
			$this->sortable_columns[$id] = (is_string($sortable_id) ? $sortable_id : $id);
		}

		if($callback !== null ) {
			$this->column_callbacks[$id] = $callback;
		}
	}

	/**
	 * Add more columns to the table
	 *
	 * @param	array	$columns	Array of column arguments:
	 *                       			string	id		ID/name of column
	 *                       			string 	title	Title of column
	 *
	 * @since	1.0.0
	 */
	public function addColumns(array $columns = array()) {
		foreach($columns as $id => $column) {
		    $column = \apply_filters($this->getTableName() . '_before_add_column', $column);

			$this->addColumn(
				(isset($column['id']) ? $columns['id'] : $id),
				$column['title'],
				(isset($column['sortable']) ? $column['sortable'] : false),
				(isset($column['callback']) ? $column['callback'] : null)
			);
		}
	}

	/**
	 * Return an array of columns
	 *
	 * @return 	array 	Columns
	 *
	 * @since	1.0.0
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * Return an array of sortable columns
	 *
	 * @return 	array 	Sortable columns
	 *
	 * @since	1.0.0
	 */
	public function get_sortable_columns() {
		return $this->sortable_columns;
	}

	/**
	 * Get per page option for screen
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	public function getPerPage() {

		if($this->args['pagination']) {
			$screen = \get_current_screen();
			$option = $screen->get_option('per_page', 'option');

			$per_page = $this->get_items_per_page($option);
		} else {
			$per_page = -1;
		}

		return $per_page;
	}

    /**
     * Add row action
     *
     * @param 	string 	$action	The ID of the action
     * @param 	string	$title	The title of the action
     * @param 	array  	$args	Additional args:
     *                       		string	class		Class(es) added to the anchor
     *                       		string	title		Title of the action
     *                       		string	action		ID of the action
     *                       		string 	action_hook	The Action hook to add to the url - Defaults to action if not set
     *                       		string	template	The template to render the action anchor
     *
     * @since	1.0.0
     */
    public function addRowAction($action, $title, $args = array()) {
        $defaults = array(
            'class' => '',
            'title' => $title,
            'action' => $action,
            'action_hook' => '',
            'template' => 'partials.table.action',
            'slug' => 'item_id'
        );

		$args = \wp_parse_args($args, $defaults);

		$this->row_actions[] = $args;
	}

	/**
	 * Add actions
	 *
	 * @param	array	$actions	Array of actions
	 *
	 * @since 	1.0.0
	 */
	public function addRowActions(array $actions) {
		foreach($actions as $id =>$action) {
			$this->addRowAction($id, '', $action);
		}
	}

	/**
	 * Prepare the table
	 *
	 * @since	1.0.0
	 */
	public function prepare_items() {

		$per_page = \apply_filters($this->getTableSlug() . '_per_page', $this->getPerPage());

		$this->items = (array) \apply_filters($this->getTableSlug() . '_get_items', array(), $this->get_pagenum(), $per_page);

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$total_items  = \apply_filters($this->getTableSlug() . '_total_items', count($this->items));

		if($this->args['pagination']) {
			if(count($this->items) > $per_page) {
				$this->items = array_slice($this->items, ($this->get_pagenum() - 1) * $per_page, $per_page);
			}

			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $per_page]);
		}
	}

	/**
	 * Echo row actions the WordPress way
	 *
	 * @param 	$actions
	 * @param 	$title
	 *
	 * @since 	1.0.0
	 */
	public function rowActions($actions, $title) {
		echo sprintf('%1$s %2$s', $title, $this->row_actions($actions));
	}

    /**
     * Display column
     *
     * @param 	$item			The item
     * @param	$column_name	The name of column
     *
     * @since	1.0.0
     */
    public function column_default($item, $column_name) {
        if(isset($this->column_callbacks[$column_name])) {
            call_user_func_array($this->column_callbacks[$column_name], array($item));
        } else {
            \do_action("mtt/theme/" . $this->slug . "/admin/table/display/column/default", $item, $column_name);
        }
        if($this->get_primary_column() === $column_name) {
            echo $this->row_actions($this->getRowActions($item));
        }
    }

	/**
	 * Get row actions based on item
	 *
	 * @param 	mixed 	$item	The item the row is showing
	 *
	 * @return 	array
	 *
	 * @since 	1.0.0
	 */
	public function getRowActions($item) {
		$actions = array();

        foreach($this->row_actions as $action) {
            $action['item'] = $item;
            $action['table_name'] = $this->getTableName();
            $actions[$action['action']] = Project::getTemplate($action['template'], $action, false, array('lib.modules.admin-tools.templates'));
        }

		return $actions;
	}

	/**
	 *  Displays the table
	 *
	 * @since    1.0.0
	 */
	public function display() {
		\ob_start();
			parent::display();
		$table = \ob_get_clean();

		\ob_start();
			$this->inlineEdit();
		$inline_edit = \ob_get_clean();

        Project::getTemplate('partials.table.table', array('table_name' =>$this->plural, 'table' => $table, 'inline_edit' => $inline_edit, 'table_name' => $this->getTableName()), true, array('lib.modules.admin-tools.templates'));
    }

    /**
     * Displays af initialized table by slug
     *
     * @param   $slug
     *
     * @return  mixed
     *
     * @since   5.0.0
     */
    public static function displayTable($slug) {

        if(isset(static::$tables[$slug])) {
            $table = static::$tables[$slug];

            return $table->display();
        }


        return false;
    }

	/**
	 * Displays Table Search box
	 *
	 * @param $slug
	 */
    public static function displayTableSearch($slug, $text, $id = null) {
	    if(isset(static::$tables[$slug])) {
		    $table = static::$tables[$slug];

		    if(is_null($id)) {
		    	$id = 'search-' . $slug;
		    }
		    return $table->search_box($text, $id);
	    }


	    return false;
    }

	/**
	 * Inline edit
	 *
	 * @since    1.0.0
	 */
	public function inlineEdit() {
		if($this->args['inline_edit_callback'] !== null || $this->args['bulk_edit_callback'] !== null) {
			$rows = array();

			if($this->args['bulk_edit_callback'] !== null) {
				$rows['bulk_edit'] = call_user_func_array($this->args['bulk_edit_callback'], array($this));
			}

			if($this->args['inline_edit_callback'] !== null) {
				$rows['inline_edit'] = call_user_func_array($this->args['inline_edit_callback'], array($this));
			}

            Project::getTemplate('partials.table.inline-edit-table', array('rows' => $rows), true, array('lib.modules.admin-tools.templates'));

			$items = \apply_filters('admin_table_inline_edit_data_items_' . $this->getTableSlug(), $this->items);

            Project::getTemplate('partials.table.inline-edit-data', array('table_name' => $this->getTableName(), 'items' => $items), true, array('lib.modules.admin-tools.templates'));
        }
    }

	/**
	 * Display single row
	 *
	 * @param 	$item	The row Item
	 *
	 * @since   1.0.0
	 */
	public function single_row($item) {
		\ob_start();
			$this->single_row_columns($item);
		$columns = \ob_get_clean();

        Project::getTemplate('partials.table.row', ['item' => $item, 'columns' => $columns], true, array('lib.templates', 'lib.modules.admin-tools.templates'));
    }

    /**
     * Display checkbox for bulk actions
     *
     * @param 	mixed 	$item The item the row is showing
     *
     * @return 	array
     *
     * @since 	2.0.0
     */
    protected function column_cb( $item ) {
        return Project::getTemplate('partials.table.column_cb', array('item_id' => $item->ID), false, array('lib.modules.admin-tools.templates'));
    }

    public function get_column_info() {
        if($this->args['use_screen_settings'] === true) {
            return parent::get_column_info();
        }

        $columns = $this->get_columns();
        $hidden  = [];

        $sortable_columns = $this->get_sortable_columns();

        $_sortable =  $sortable_columns;

        $sortable = array();
        foreach ( $_sortable as $id => $data ) {
            if ( empty( $data ) ) {
                continue;
            }

            $data = (array) $data;
            if ( ! isset( $data[1] ) ) {
                $data[1] = false;
            }

            $sortable[ $id ] = $data;
        }

        $primary               = $this->get_primary_column_name();
        $this->_column_headers = array( $columns, $hidden, $sortable, $primary );

        return $this->_column_headers;
    }

}