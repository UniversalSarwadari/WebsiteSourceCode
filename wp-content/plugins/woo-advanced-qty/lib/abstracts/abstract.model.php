<?php namespace Morningtrain\WooAdvancedQTY\Lib\Abstracts;

use Morningtrain\WooAdvancedQTY\Lib\Tools\Loader;
use Morningtrain\WooAdvancedQTY\Lib\Traits\SessionInstance;

/**
 * This class helps using WordPress database without custom post types
 *
 * @since 1.0.0
 */
abstract class Model {

	/**
	 * Lets us save instances temporary in user sessions
	 */
	use SessionInstance;

	// MODEL DB TABLE
	/**
	 * The database table name
	 *
	 * @var 	string
	 *
	 * @since 	1.0.0
	 */
	protected static $table_name = '';

	/**
	 * Columns in the database table
	 *
	 * @var array
	 *
	 * @since    1.0.0
	 */
	protected static $columns = array();

	/**
	 * Columns extra
	 * @var array
	 */
	protected static $columns_extra = array(
		'created_date' => array(
			'type' => 'datetime'
		),
		'created_by' => array(
			'type' => 'int',
			'unsigned' => true
		),
		'updated_date' => array(
			'type' => 'datetime'
		),
		'updated_by' => array(
			'type' => 'int',
			'unsigned' => true
		)
	);

	/**
	 * Primary database table column
	 *
	 * @var string
	 *
	 * @since    1.0.0
	 */
	protected static $primary_key = 'id';

	// META DB TABLE
	/**
	 * Meta table name
	 *
	 * @var string
	 *
	 * @since 5.0.0
	 */
	protected static $meta_table_name = '';

	/**
	 * Meta table columns
	 *
	 * @var array
	 *
	 * @since 5.0.0
	 */
	protected static $meta_columns = array(
		'meta_id' => array(
			'type' => 'bigint',
			'unsigned' => true,
			'not_null' => true
		),
		'parent_id' => array(
			'type' => 'int',
			'unsigned' => true
		),
		'meta_key' => array(
			'type' => 'varchar(256)'
		),
		'meta_value' => array(
			'type' => 'longtext'
		)
	);

	/**
	 * Meta Table Primary key
	 *
	 * @var string
	 *
	 * @since 5.0.0
	 */
	protected static $meta_primary_key = 'meta_id';

	// OTHER INFO
	/**
	 * Version of the database
	 *
	 * This is used for table update.
	 *
	 * @var string
	 *
	 * @since    1.0.0
	 */
	protected static $db_version = '1.0.0';

	// HELPERS
	/**
	 * Total count of last query
	 *
	 * @var    int
	 *
	 * @since    1.0.0
	 */
	protected static $total_num_rows = 0;

	/**
	 * Store runtime cache
	 *
	 * @var    array
	 *
	 * @since    1.0.0
	 */
	protected static $cache = array();

	/**
	 * Meta data for the Meta table
	 *
	 * @var array
	 */
	protected $meta_data = array();

	// INITIALIZERS
	/**
	 * Model constructor.
	 *
	 * @param 	array 	$properties 	Array of properties
	 *
	 * @since 	1.0.0
	 */
	public function __construct($properties = array()) {
		$this->init($properties);

		return $this;
	}

	/**
	 * Initialize properties
	 *
	 * @param 	mixed	$properties		If is int, it will assume it is an ID, and get properties from the database
	 *
	 * @since   1.0.0
	 */
	protected function init($properties = array()) {
		$this->{static::$primary_key} = 0;

		if(is_int($properties)) {
			$properties = $this->getFirst(array(
				'where' => array(
					'id' => $properties,
				),
				'return' => 'ARRAY_A',
			));
		}
		static::setProperties($properties);
	}

	/**
	 * Set properties and meta
	 *
	 * @param array $properties
	 */
	public function setProperties($properties = array()) {
		if(is_array($properties)) {
			foreach($properties as $key => $property) {
				$this->{$key} = $property;
			}
		}
	}

	/**
	 * Register Model
	 *
	 * @since   5.0.0
	 */
	public static function register() {
		static::createTable();
		static::createMetaTable();
		static::registerTables();
		static::registerModelActions();
		static::registerModelFilters();
		static::registerSessionInstanceTrait();
	}

	/**
	 * Register Actions specified for the Model
	 *
	 * @since 5.0.0
	 */
	protected static function registerModelActions() {

	}

	/**
	 * Register Filters specified for the Model
	 *
	 * @since 5.0.0
	 */
	protected static function registerModelFilters() {
		Loader::addFilter(static::getFilterName('save', 'columns'), static::class, 'prepareColumnBasedOnType', 10, 2);
	}

	/**
	 * Prepare column data for the DB based on column type
	 * @param $columns
	 * @param $instance
	 *
	 * @return mixed
	 */
	public static function prepareColumnBasedOnType($columns, $instance) {

		foreach($columns as $key => &$value) {
			switch(strtolower(static::getColumnType($key))) {
				case 'datetime':
					if($value === '') {
						$value = null;
					} else if(is_a($value, 'DateTime')) {
						$value = $value->format('Y-m-d H:i:s');
					}
					break;
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'bigint':
					if($value !== null) {
						$value = (int)$value;
					}
					break;
				case 'bit(1)':
					$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
					break;
			}
		}

		return $columns;
	}


	// META

	/**
	 * Get meta data for model
	 *
	 * @param        $key
	 * @param string $default
	 *
	 * @return mixed|null
	 */
	public function getMeta($key = null, $default = '') {
		// Get all meta if key is null
		if(is_null($key)) {
			$meta_data = get_metadata(static::getSlug(), $this->getPrimaryKey());

			if (!is_array($meta_data)) {
				$meta_data = array();
			}

			$meta_from_db = array_map(function ($v) {return $v[0];}, $meta_data);

			$this->meta_data = array_merge($this->meta_data, $meta_from_db);
			return $this->meta_data;
		}

		if(!isset($this->meta_data[$key])) {
			$meta = static::getMetaFromDB($this->getPrimaryKey(), $key);

			if($meta === '') {
				return $default;
			}

			$this->meta_data[$key] = $meta;
		}

		return $this->meta_data[$key];
	}

	/**
	 * Get meta directly from DB
	 *
	 * @param        $id
	 * @param        $key
	 * @param string $default
	 *
	 * @return mixed|string
	 */
	public static function getMetaFromDB($id, $key, $default = '') {
		if($id < 1) {
			return '';
		}

		$meta = get_metadata(static::getSlug(), $id, $key, true);

		if($meta !== '') {
			return $meta;
		}

		return $default;
	}

	/**
	 * Update Meta
	 * @param $key
	 * @param $value
	 *
	 * @return bool|int
	 * @throws \ReflectionException
	 */
	public function updateMeta($key, $value = null) {
		// if
		if(is_array($key)) {
			foreach($key as $_key => $_value) {
				$this->updateMeta($_key, $_value);
			}
			return;
		}
		$this->meta_data[$key] = $value;
		return update_metadata($this->getSlug(), $this->getPrimaryKey(), $key, $value);
	}

	/**
	 * Update meta
	 * @see updateMeta()
	 * @param $key
	 * @param $value
	 *
	 * @return bool|int
	 * @throws \ReflectionException
	 */
	public function addMeta($key, $value) {
		return $this->updateMeta($key, $value);
	}

	/**
	 * Delete meta data
	 * @param $key
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function deleteMeta($key = null) {
		if(empty(static::getMetaTableName(false))) {
			return;
		}
		// if no key is set delete all meta
		if($key == null) {
			global $wpdb;
			return $wpdb->delete(static::getMetaTableName(), array(static::getMetaTableParentColumnName() => $this->getPrimaryKey()));
		}
		unset($this->meta_data[$key]);
		return delete_metadata($this->getSlug(), $this->getPrimaryKey(), $key);
	}

	/** Updates or deletes a metafield
	 * @param string $key
	 * @param mixed $value
	 * @throws \ReflectionException
	 */
	public function updateOrDeleteMeta($key, $value)
	{
		if(!empty($value)){
			$this->setMeta($key,$value);
			$this->save();
		}else{
			$this->deleteMeta($key);
		}
	}

	// SETTERS
	/**
	 * set property or meta
	 *
	 * @param $key
	 * @param $value
	 */
	public function __set($key, $value) {
		if(!static::tableColumnExists($key) && !property_exists($this, $key)) {
			$this->meta_data[$key] = $value;
			return;
		}

		$this->{$key} = $value;
	}

	/**
	 * Set meta data on object
	 *
	 * @param $key
	 * @param $value
	 */
	public function setMeta($key, $value) {
		$this->meta_data[$key] = $value;
	}

	// GETTERS

	/**
	 * Generates slug from class name
	 *
	 * @return string|\WP_Error
	 */
	public static function getSlug()
	{
		try{
			return strtolower((new \ReflectionClass(get_called_class()))->getShortName());
		}
		catch (\ReflectionException $e){
			return new \WP_Error($e->getCode(),$e->getMessage());
		}
	}

	/**
	 * Returns table name with WordPress databses prefix
	 *
	 * @param bool $prefixed
	 *
	 * @return    string    Table name
	 *
	 * @since    1.0.0
	 */
	public static function getTableName($prefixed = true) {
		if(!empty(static::$table_name)) {
			$_table_name = '';

			if(isset($prefixed)) {
				global $wpdb;

				$_table_name .= $wpdb->prefix;
			}

			$_table_name .= static::$table_name;

			return $_table_name;
		}

		return null;
	}

	/**
	 * Returns the meta table name withs WordPress database prefix
	 *
	 * @param bool $prefixed
	 *
	 * @return string
	 */
	public static function getMetaTableName($prefixed = true) {
		if(!empty(static::$meta_table_name)) {
			$_table_name = '';

			if(isset($prefixed)) {
				global $wpdb;

				$_table_name .= $wpdb->prefix;
			}

			$_table_name .= static::$meta_table_name;

			return $_table_name;
		}

		return null;
	}

	/**
	 * Returns the name of the primary key
	 *
	 * @return 	string	Name of primary key
	 *
	 * @since   1.0.0
	 */
	public static function getPrimaryKeyName() {
		return static::$primary_key;
	}

	/**
	 * Get Meta Table Primary Key name
	 * @return string
	 *
	 * @since   5.0.0
	 */
	public static function getMetaPrimaryKeyName() {
		return static::$meta_primary_key;
	}

	/**
	 * Returns table columns with primary key and created/updated columns
	 *
	 * @return array
	 *
	 * @since   5.0.0
	 */
	public static function getTableColumns() {
		$columns = static::$columns;

		if(!isset($columns[static::getPrimaryKeyName()])) {
			$primary_key = array(
				static::getPrimaryKeyName() => array(
					'type' => 'int',
					'unsigned' => true,
					'not_null' => true,
				)
			);

			$columns = array_merge($primary_key, $columns);
		}

		$columns = array_merge($columns, static::$columns_extra);

		return \apply_filters(static::getFilterName('get', 'table', 'columns'),$columns);
	}

	/**
	 * Return name of parent id column in meta table
	 * @return string
	 * @throws \ReflectionException
	 */
	public static function getMetaTableParentColumnName() {
		return static::getSlug() . '_id';
	}

	/**
	 * Rename meta table columns (parent_id)
	 *
	 * @return array
	 * @throws \ReflectionException
	 */
	public static function getMetaTableColumns() {
		$columns = static::$meta_columns;

		// Replaces parent_id column with correct object type name
		if(isset($columns['parent_id'])) {
			$columns[self::getMetaTableParentColumnName()] = $columns['parent_id'];
			unset($columns['parent_id']);
		}

		return $columns;
	}

	/**
	 * Creates a filter name
	 *
	 * @param $function
	 * @param $action
	 *
	 * @return string
	 *
	 * @throws \ReflectionException
	 * @since 5.0.0
	 */
	public static function getFilterName($action, $support, ...$args) {
		return static::getHookName(...func_get_args());
	}

	/**
	 * Creates a filter name
	 *
	 * @param array $args
	 * @return string
	 *
	 * @throws \ReflectionException
	 * @since 5.0.0
	 */
	public static function getActionName(...$args) {
		return static::getHookName(...func_get_args());
	}

	/**
	 * Get Hook Name
	 * @param mixed ...$args
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	public static function getHookName(...$args)
	{
		$parts = array('mtt', 'theme', static::getSlug());
		$parts = array_filter($parts);
		$parts = array_merge($parts, func_get_args());
		return implode('/', $parts);
	}

	/**
	 * Magic getter method to ensure we get correct data
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function __get($key) {
		if($key === 'ID') {
			return $this->getPrimaryKey();
		}

		if(property_exists($this, $key)) {
			return $this->{$key};
		}

		if($meta = $this->getMeta($key, false)) {
			return $meta;
		}

		if(!isset($this->{$key})){
			return null;
		}

		return $this->{$key};
	}

	/**
	 * Magic isset method to ensure we get correct data
	 *
	 * @param $key
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function __isset($key) {
		if($key === 'ID') {
			return isset($this->{$this->getPrimaryKeyName()});
		}

		if(isset($this->{$key})) {
			return true;
		}

		// meta data
		if(isset($this->meta_data{$key}) && $this->meta_data{$key} !== '') {
			return true;
		}

		$meta = static::getMetaFromDB($this->getPrimaryKey(), $key);
		if($meta !== '') {
			return true;
		}

		return isset($this->{$key});
	}

	/**
	 * Get all table columns data in object
	 * @return array
	 */
	public function getColumnData() {
		$columns = get_object_vars($this);

		foreach($columns as $key => $value) {
			if(!static::tableColumnExists($key)) {
				unset($columns[$key]);
			}
		}

		return $columns;
	}

	/**
	 * Get DB Table column type
	 * @param $column_name
	 *
	 * @return mixed|null
	 */
	public static function getColumnType($column_name) {
		if(isset(static::getTableColumns()[$column_name]) && isset(static::getTableColumns()[$column_name]['type'])) {
			return static::getTableColumns()[$column_name]['type'];
		}
		return null;
	}

	/**
	 * Get the primary key
	 *
	 * @return mixed
	 *
	 * @since    3.0.3
	 */
	public function getPrimaryKey() {
		if(isset($this->{static::getPrimaryKeyName()})) {
			return $this->{static::getPrimaryKeyName()};
		}
		return null;
	}

	// HELPERS
	/**
	 * Delete in database
	 *
	 * @return	bool	True if succesed, false if failed
	 *
	 * @since    1.0.0
	 */
	public function delete() {
		return $this->deleteByID($this->getPrimaryKey());
	}

	/**
	 * Save the current instance to the database
	 *
	 * @return bool|\WP_Error
	 *
	 * @throws \ReflectionException
	 * @since    1.0.0
	 */
	public function save() {
		$columns = array_merge($this->getColumnData(), $this->meta_data);

		$columns = \apply_filters(static::getFilterName('save', 'columns'), $columns, $this->{static::$primary_key});
		\do_action(static::getHookName('save', 'before'), $columns, $this);

		if(isset($columns[static::$primary_key]) && $columns[static::$primary_key] > 0) {
			$return = $this->update($columns[static::$primary_key], $columns, $this);
		} else {
			$return = $this->insert($columns, $this);
			if(is_a($return, get_called_class())) {
				$return = true;
			}
		}

		\do_action(static::getHookName('save', 'after'), $columns, $this, $return);

		return $return;
	}

	/**
	 * Insert object in db
	 *
	 * @param array $columns
	 *
	 * @param null  $instance
	 *
	 * @return bool|static|\WP_Error
	 *
	 * @throws \ReflectionException
	 * @since	1.0.0
	 */
	public static function insert(array $columns = array(), &$instance = null) {
		global $wpdb;

		// Create new instance if not passed to function
		if(is_null($instance)) {
			$instance = new static($columns);
		}

		$columns['created_date'] = \current_time('mysql');
		$columns['created_by'] = \get_current_user_id();

		$columns = \apply_filters(static::getFilterName('insert', 'columns'), $columns, $instance);

		$meta_data = static::extractMetaDataFromColumns($columns);

		// Validate columns
		$valid = static::validateColumns($columns, $instance);
		if(!$valid || is_wp_error($valid)) {
			return $valid;
		}

		if(!$wpdb->insert(static::getTableName(), $columns)) {
			return new \WP_Error('failed', $wpdb->last_error);
		};

		// Set ID and columns
		$columns[static::getPrimaryKeyName()] = $wpdb->insert_id;
		$instance->setProperties($columns);

		// Update meta
		$instance->updateMeta($meta_data);

		\do_action(static::getHookName('insert', 'after'), $columns, $instance);

		return $instance;
	}

	/**
	 * Check if table column exists
	 *
	 * @param $column_name
	 *
	 * @return bool
	 */
	public static function tableColumnExists($column_name) {
		$columns = static::getTableColumns();

		return isset($columns[$column_name]);
	}

	/**
	 * Create or update a record matching the attributes, and fill it with values.
	 *
	 * @param array $attributes Attributes to match
	 * @param array $values Values to update
	 *
	 * @return static
	 * @throws \ReflectionException
	 */
	public static function updateOrCreate(array $attributes, array $values = [])
	{
		$existing = static::getFirstBy($attributes);

		if($existing){
			static::update($existing->getID(), $values, $existing);
			return $existing;
		}

		$newInstance = new static($attributes + $values);
		$newInstance->save();

		return $newInstance;
	}

	/**
	 * Get the first record that matches attributes, or a new instance
	 *
	 * @param array $attributes
	 *
	 * @return static
	 */
	public static function firstOrNew(array $attributes)
	{
		$existing = static::getFirstBy($attributes);

		if($existing){
			return $existing;
		}

		return new static($attributes);
	}

	/**
	 * Get the first record that matches attributes, or create a new record
	 *
	 * @param array $attributes
	 *
	 * @return static
	 */
	public static function firstOrCreate(array $attributes)
	{
		$existing = static::getFirstBy($attributes);

		if($existing){
			return $existing;
		}

		$new_instance = new static($attributes);

		$new_instance->save();

		return $new_instance;
	}

	/**
	 * Update object in database
	 *
	 * @param       $id
	 * @param array $columns
	 *
	 * @param null  $object
	 *
	 * @return bool|\WP_Error
	 *
	 * @throws \ReflectionException
	 * @since    1.0.0
	 */
	public static function update($id, $columns = array(), &$instance = null) {
		global $wpdb;

		// Create new instance if not passed to function
		if(is_null($instance)) {
			$instance = static::getByPrimaryKey($id);
		}

		$columns['updated_date'] = \current_time('mysql');
		$columns['updated_by'] = \get_current_user_id();

		$columns = \apply_filters(static::getFilterName('update', 'columns'), $columns, $instance);

		$meta_data = static::extractMetaDataFromColumns($columns);

		// Validate columns
		$valid = static::validateColumns($columns, $instance);
		if(!$valid || is_wp_error($valid)) {
			return $valid;
		}

		if(!$wpdb->update(static::getTableName(), $columns, array(static::$primary_key  => $id))) {
			return new \WP_Error('failed', $wpdb->last_error);
		};

		$instance->setProperties($columns);
		$instance->updateMeta($meta_data);

		\do_action(static::getHookName('update', 'after'), $columns, $id);

		return true;
	}

	/**
	 * Validates columns one by one,
	 *
	 * @param $columns
	 *
	 * @param null $instance
	 *
	 * @return bool|\WP_Error
	 * @throws \ReflectionException
	 */
	public static function validateColumns($columns, &$instance = null) {
		$valid = true;
		foreach($columns as $name => $value) {
			$_error = static::validateColumn($name, $value, $instance);

			if(is_wp_error($_error)) {
				if(!is_wp_error($valid)) {
					$valid = new \WP_Error();
				}

				$valid->errors = array_merge_recursive($valid->errors, $_error->errors);
				$valid->error_data = array_merge_recursive($valid->error_data, $_error->error_data);
			}
		}

		/**
		 * Filter to change errors
		 *
		 * mtt/theme/model/$slug/columns/validate/errors
		 *
		 * @param bool|\WP_Error $valid valid or error
		 * @param array $columns column_name => value
		 * @param static|null $instance Instance for extra information
		 *
		 * @return bool|\WP_Error return WP_Error object if not valid
		 */
		$valid = apply_filters(static::getFilterName('columns', 'validate', 'errors'), $valid, $columns, $instance);

		return $valid;
	}

	/**
	 * @param $column
	 * @param $value
	 * @param $instance
	 *
	 * @return bool|\WP_Error
	 * @throws \ReflectionException
	 */
	public static function validateColumn($column, $value, &$instance = null) {
		$valid = true;

		/**
		 * Filters to change errors
		 *
		 * mtt/theme/model/$slug/column/validate/errors
		 * mtt/theme/model/$slug/column/$column/validate/errors
		 *
		 * @param bool|\WP_Error $valid valid or error
		 * @param array $column column_name
		 * @param mixed $value value
		 * @param static|null $instance Instance for extra information
		 *
		 * @return bool|\WP_Error return WP_Error object if not valid
		 */
		$valid = apply_filters(static::getFilterName('column', 'validate', 'errors'), $valid, $column, $value, $instance);
		$valid = apply_filters(static::getFilterName('column', $column, 'validate', 'errors'), $valid, $column, $value, $instance);

		return $valid;
	}

	/**
	 * filter all non table columns out of array and return in another array
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public static function extractMetaDataFromColumns(&$columns) {
		$meta_data = array();

		foreach($columns as $column => $data) {
			if(!static::tableColumnExists($column)) {
				$meta_data[$column] = $data;
				unset($columns[$column]);
			}
		}

		return $meta_data;
	}

	/**
	 * Check if table needs to be updated
	 *
	 * @since   1.0.0
	 */
	public static function updateTable() {
		static::createTable();
		static::createMetaTable();
	}

	/**
	 * register tables in $wpdb for use with $wpdb functionality (ex. meta)
	 *
	 * @throws \ReflectionException
	 */
	public static function registerTables() {
		global $wpdb;

		$type = static::getSlug();

		$wpdb->tables[] = static::getTableName(false);
		$wpdb->tables[] = static::getMetaTableName(false);
		$wpdb->{$type} = static::getTableName();
		$wpdb->{$type.'meta'} = static::getMetaTableName();
	}

	/**
	 * Creates or update table
	 *
	 * @since    1.0.0
	 */
	public static function createTable() {
		static::createDBTable(static::getTableName(), static::getTableColumns(), static::getPrimaryKeyName(), static::$db_version);
	}

	/**
	 * Creates Meta Table
	 *
	 * @since   5.0.0
	 */
	public static function createMetaTable() {
		static::createDBTable(static::getMetaTableName(), static::getMetaTableColumns(), static::getMetaPrimaryKeyName(), static::$db_version);
	}

	/**
	 * Creates Table - Rewritten and sepperated from createTable
	 *
	 * @param $table_name
	 * @param $columns
	 * @param $primary_key
	 * @param $version
	 *
	 * @since   5.0.0
	 */
	public static function createDBTable($table_name, $columns, $primary_key, $version) {
		global $wpdb;

		if(empty($table_name) || $version == \get_option($table_name . '_db_version')) {
			return;
		}



		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset = $wpdb->get_charset_collate();

		$sql_columns = "";

		foreach($columns as $name => $column) {

			$default_column_value = '';
			if(isset($column['default'])) {
				$default_column_value = is_string($column['default']) ? "'{$column['default']}'" : $column['default'];
			}

			$sql_columns .= $name . " " . $column['type'] . (isset($column['unsigned']) && $column['unsigned'] ? " unsigned" : "") . (isset($column['default']) ? " DEFAULT $default_column_value" : "") . (isset($column['not_null']) && $column['not_null'] ? " NOT NULL" : "") . ($name === $primary_key ? " AUTO_INCREMENT" : "") . ", 
				";
		}

		$sql = "CREATE TABLE $table_name (
			$sql_columns
				PRIMARY KEY  ($primary_key),
				UNIQUE KEY $primary_key ($primary_key)
			) $charset;";

		\dbDelta($sql);

		\update_option($table_name . '_db_version', $version);
	}

	/**
	 * Drops table
	 *
	 * @since    1.0.0
	 */
	public static function dropTable() {
		global $wpdb;

		$wpdb->query("DROP TABLE IF EXISTS " . static::getTableName());

		\delete_option(static::$table_name . '_db_version');
	}

	/**
	 * Truncate the model table
	 */
	public static function truncateTable()
	{
		global $wpdb;

		$wpdb->query('TRUNCATE TABLE ' . static::getTableName());
	}

	/**
	 * alias of truncateTable()
	 */
	public static function deleteAll()
	{
		static::truncateTable();
		\do_action(static::getActionName("delete","all"));

	}

	/**
	 * Helper function to perform SQL query
	 *
	 * @param        $sql
	 * @param bool   $single
	 * @param string $return
	 *
	 * @return static
	 *
	 * @since    1.0.0
	 */
	protected static function performSQL($sql, $single = false, $return = 'OBJECT') {
		global $wpdb;

		if($single) {
			$results = $wpdb->get_row($sql, 'ARRAY_A');

			if($results !== NULL) {
				if($return != 'ARRAY_A') {
					$results = new static($results);
				}
			}
		} else {
			$results = $wpdb->get_results($sql, 'ARRAY_A');

			if($return != 'ARRAY_A') {
				foreach($results as $key => $result) {
					$results[$key] = new static($result);
				}
			}
		}

		return $results;
	}

	/**
	 * Get obejcts from databse using raw sql statement
	 *
	 * @param	string 	$sql	SQL statement
	 * @param 	bool 	$cache	Should WordPress get the result from the cache if it exists
	 *
	 * @return 	array	Array of objects
	 *
	 * @since    1.0.0
	 */
	public static function getRaw($sql, $cache = true, $single = false) {
		if($single && !strpos($sql, 'LIMIT')) {
			$sql = trim($sql) . ' LIMIT 1';
		}

		$cache_key = md5(static::$table_name . $sql);

		$results = false;

		if($cache) {
			if(array_key_exists($cache_key, static::$cache)) {
				$results = static::$cache[$cache_key];
			} else {
				$results = \wp_cache_get($cache_key);
			}
		}

		if(!$results) {
			$results = static::performSQL($sql, $single);

			\wp_cache_set($cache_key, $results);
		}

		static::$cache[$cache_key] = $results;

		return $results;
	}

	/**
	 * Get single object from data base using raw sql
	 *
	 * @param      $sql
	 * @param bool $cache
	 *
	 * @return array
	 *
	 * @since    1.0.0
	 */
	public static function getFirstRaw($sql, $cache = true) {
		return static::getRaw($sql, $cache, true);
	}

	/**
	 * Get objects from database using arguments
	 *
	 * @param 	array 	$args	Arguments to use for the sql query
	 * @param 	bool  	$cache	Should WordPress get the result from the cache if it exists
	 *
	 * @return array
	 *
	 * @since    1.0.0
	 */
	public static function get(array $args = array(), $cache = true, $single = false) {
		$defaults = array(
			'posts_per_page' => $single ? 1 : \get_option('posts_per_page'),
			'offset' => 0,
			'page' => 1,
			'orderby' => static::$primary_key,
			'order' => 'ASC',
			'where' => array(),
			'columns' => '*',
			'with' => array(),
			'return' => 'OBJECT',
			'joins' => '',
			'as' => '',
		);

		$args = \wp_parse_args($args, $defaults);

		$cache_key = md5(static::$table_name . serialize($args));

		$results = false;

		if($cache) {
			if(array_key_exists($cache_key, static::$cache) && array_key_exists($cache_key . '_count_total', static::$cache)) {
				$results = static::$cache[$cache_key];
				static::$total_num_rows = static::$cache[$cache_key . '_count_total'];
			} else {
				$results = \wp_cache_get($cache_key);
				static::$total_num_rows = \wp_cache_get($cache_key . '_count_total');
			}
		}

		if(!$results) {
			global $wpdb;

			$select = is_array($args['columns']) ? \implode(', ', $args['columns']) : $args['columns'];

			$from = static::getTableName();

			$as = empty($args['as']) ? '' : 'AS ' . $args['as'];

			$where = static::createWhereSQLPart($args['where']);

			$offset = $args['offset'] + (($args['page'] * $args['posts_per_page']) - $args['posts_per_page']);

			$post_per_page = $args['posts_per_page'] > 0 ? $args['posts_per_page'] : PHP_INT_MAX;

			$group_by = empty($args['groupby']) ? '' : 'GROUP BY ' . $args['groupby'];

			if(!empty($args['joins']) && $args['orderby'] === static::getPrimaryKeyName()){
				$args['orderby'] = static::getTableName().'.'.static::getPrimaryKeyName();
			}

			$sql = \implode(' ', array(
				'SELECT',
				'SQL_CALC_FOUND_ROWS',
				$select,
				'FROM',
				$from,
				$as,
				$args['joins'],
				$where,
				$group_by,
				'ORDER BY',
				$args['orderby'],
				$args['order'],
				'LIMIT',
				$post_per_page,
				'OFFSET',
				$offset,
			));

			$results = static::performSQL($sql, $single, $args['return']);

			static::$total_num_rows = $wpdb->get_var('SELECT FOUND_ROWS()');

			\wp_cache_set($cache_key . '_count_total', static::$total_num_rows);

			\wp_cache_set($cache_key, $results);
		}

		static::$cache[$cache_key] = $results;
		static::$cache[$cache_key . '_count_total'] = static::$total_num_rows;

		return $results;
	}

	/**
	 * Get all
	 *
	 * @param array $args
	 * @param bool  $cache
	 *
	 * @return array
	 *
	 * @since    3.0.3
	 */
	public static function getAll(array $args = array(), $cache = true) {
		$defaults = array(
			'posts_per_page' => -1,
		);

		$args = \wp_parse_args($args, $defaults);

		return static::get($args);
	}

	/**
	 * Get first model by column(s) and/or arg(s)
	 * @param $columns
	 * @param null $value
	 * @param array $args
	 * @param bool  $cache
	 * @return static
	 */
	public static function getFirstBy($columns, $value = null, array $args = array(), $cache = false)
	{
		return static::getBy($columns, $value, $args, true, $cache);
	}

	/**
	 * Get by column(s)
	 *
	 * @param string|array      $columns column name or coloumn name => value pair to filter by
	 * @param       $value
	 * @param array $args
	 * @param bool  $single
	 * @param bool  $cache
	 *
	 * @return static|static[]
	 *
	 * @since    3.0.3
	 */
	public static function getBy($columns, $value = null, array $args = array(), $single = false, $cache = false) {

		$defaults = array(
			'where' => array()
		);

		$args = \wp_parse_args($args, $defaults);

		if(is_array($columns)) {
			foreach($columns as $_column => $_value) {
				$args['where'][$_column] = $_value;
			}
		} else {
			$args['where'][$columns] = $value;
		}

		return static::get($args, $cache, $single);
	}

	/**
	 * Get by primary key
	 *
	 * @param       $value
	 * @param array $args
	 * @param bool  $cache
	 *
	 * @return static
	 *
	 * @since    3.0.3
	 */
	public static function getByPrimaryKey($value, array $args = array(), $cache = true) {
		return static::getFirstBy(static::getPrimaryKeyName(), $value, $args, $cache);
	}

	/**
	 * Get single object form data base
	 *
	 * @param 	array	$args
	 * @param	bool	$cache
	 *
	 * @return	array
	 *
	 * @since   1.0.0
	 */
	public static function getFirst(array $args = array(), $cache = true) {
		return static::get($args, $cache, true);
	}

	/**
	 * Returns the total number of objects from last query (without LIMIT)
	 *
	 * @return int
	 *
	 * @since    1.0.0
	 */
	public static function getTotalCount() {
		return static::$total_num_rows;
	}

	/**
	 * Creates the where part of the sql query
	 *
	 * @param 	array	$pieces		Pieces $column => $value, array($column, $value) or array($column, $operator, $value)
	 *
	 * @return 	string
	 *
	 * @since   1.0.0
	 */
	protected static function createWhereSQLPart($pieces = array(), $operator = 'AND') {
		if(!is_array($pieces)) {
			return $pieces;
		}

		if(empty($pieces)) {
			return '';
		}

		$str = 'WHERE';

		$str .= static::createWhereSQLPartPiece($pieces, $operator);

		return $str;
	}

	/**
	 * Creates every piece of the where sql part
	 * @param        $pieces
	 * @param string $operator
	 *
	 * @return string
	 *
	 * @since    3.0.3
	 */
	protected static function createWhereSQLPartPiece($pieces, $operator = 'AND') {
		if(!is_array($pieces)) {
			return $pieces;
		}
		if(empty($pieces)) {
			return '';
		}

		global $wpdb;

		if(isset($pieces['relation'])) {
			$operator = $pieces['relation'];
			unset($pieces['relation']);
		}

		$str = ' (';

		$comparisons = array();

		foreach($pieces as $key => $piece) {
			if(is_string($piece) && is_int($key)) {
				$comparisons[] = $piece;
			} elseif(is_array($piece) && isset($piece['relation'])) {
				$comparisons[] = static::createWhereSQLPartPiece($piece, $piece['relation']);
			} else {
				$compare_operator = "=";

				if(is_array($piece)) {
					if(count($piece) === 2) {
						$column = $piece[0];
						$value = $piece[1];
					} else if(count($piece) === 3) {
						$column = $piece[0];
						$compare_operator = $piece[1];
						$value = $piece[2];
					}
				} else {
					$column = $key;
					$value = $piece;
				}

				if($value === 'IS NULL' || $value === 'IS NOT NULL'){
					$comparisons[] = "$column $value";
				}
				else if(($compare_operator == 'IN' || $compare_operator == 'NOT IN') && is_array($value)) {
					$value = '(' . implode(',', $value) . ')';
					$comparisons[] = "$column $compare_operator " . $value;
				} else {
					if(is_int($value)) {
						$value_type = "%d";
					} else if(is_float($value)) {
						$value_type = "%s";
					} else {
						$value_type = "%s";
					}
					$sql = "$column $compare_operator $value_type";

					$comparisons[] = $wpdb->prepare($sql, $value);
				}
			}
		}

		$str .= \implode(' ' . $operator . ' ', $comparisons);

		$str .= ')';

		return $str;
	}

	/**
	 * Delete row by id
	 *
	 * @param       $id
	 *
	 * @return bool
	 *
	 * @since   1.0.0
	 */
	public static function deleteByID($id) {
		return static::deleteWhere(array(static::getPrimaryKeyName() => $id));
	}

	/**
	 * Delete rows where coloumns = value
	 *
	 * @param 	array 	$columns	Column => value to match
	 *
	 * @return	mixed
	 *
	 * @since   1.0.0
	 */
	public static function deleteWhere(array $columns = array()) {
		global $wpdb;

		$rows = static::getBy($columns, null, array('posts_per_page' => -1), false);

		$ids = array_column($rows, static::getPrimaryKeyName());
		$ids = implode( ',', array_map( 'absint', $ids ) );

		$table_name = static::getTableName();
		$primary_key = static::getPrimaryKeyName();

		$result = $wpdb->query( "DELETE FROM {$table_name} WHERE {$primary_key} IN($ids)" );

		if($result) {
			foreach($rows as $row) {
				$row->deleteMeta();
			}
		}

		\do_action(static::getActionName("delete","after"), $columns, $result);

		return $result;
	}

	/**
	 * Check if object exists
	 *
	 * @param array $columns
	 *
	 * @return bool
	 *
	 * @since    1.0.0
	 */
	public static function exists(array $columns = array(), $except = array()) {
		global $wpdb;

		if(empty($columns)) {
			return false;
		}

		$sql = "SELECT " . static::$primary_key . " FROM " . static::getTableName() . " WHERE ";

		foreach($columns as $key => $value) {
			$sql .= $wpdb->prepare("$key = %s && ", $value);
		}

		foreach($except as $key => $value) {
			$sql .= $wpdb->prepare("$key != %s && ", $value);
		}

		$sql = trim($sql, ' \t\n\r\0\x0B&');

		$result = static::getRaw($sql);

		return (!empty($result) && is_array($result));
	}

	/** Checks whether $object is a member of current model class
	 * @param $object
	 * @return bool
	 */
	public static function isA($object)
	{
		return is_a($object,static::class);
	}

	/**
	 * Alias for getPrimaryKey()
	 * @see getPrimaryKey()
	 *
	 * @return mixed
	 */
	public function getID() {
		return $this->getPrimaryKey();
	}

	/**
	 * Takes an ACF post request index by field ids
	 * and transforms into valid column values for model
	 * Returns assoc array of columns with values to easily
	 * update model props with ->setProperties()
	 *
	 * NOTE: Make sure to use model db column names as name for field
	 *
	 * @param array $data $_POST['acf'] usually
	 * @param bool $filter_non_column_keys
	 * @return array
	 */
	public function propertiesFromACFPost($data, $field_group, $filter_non_column_keys = false)
	{
		$fields = acf_get_fields($field_group);

		$mapped_fields = [];
		foreach($fields as $field){
			$mapped_fields[$field['name']] = $field;
		}

		$mapped = array_map(function ($field_object) {
			return $field_object['key'];
		}, $mapped_fields);

		if($filter_non_column_keys === true) {
			$mapped = array_filter($mapped, function ($key) {
				return isset(static::$columns[$key]);
			}, ARRAY_FILTER_USE_KEY);
		}

		return array_map(function($key) use ($data){
			return $data[$key];
		},$mapped);
	}
}
