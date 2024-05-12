<?php namespace Morningtrain\WooAdvancedQTY\Lib\Modules\JobQueue\Abstracts;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Model;
use Morningtrain\WooAdvancedQTY\Lib\Abstracts\Project;

class Job extends Model {
	/**
	 * Override to add another job queue
	 * @var string
	 */
	protected static $table_name = 'job_queue';

	/**
	 * DB Version
	 * @var string
	 */
	protected static $db_version = '1.0.0';

	/**
	 * DB Columns
	 * @var array
	 */
	protected static $columns = array(
		'date'      => array(
			'type'     => 'datetime',
			'not_null' => TRUE,
		),
		'callback'  => array(
			'type'     => 'varchar(256)',
			'not_null' => TRUE,
		),
		'component' => array(
			'type' => 'varchar(256)',
		),
		'arg'       => array(
			'type' => 'text',
		),
		'priority'  => array(
			'type'     => 'int',
			'default'  => 10,
			'not_null' => TRUE,
		),
		'run_date'  => array(
			'type' => 'datetime',
		),
		'result'    => array(
			'type' => 'text',
		),
	);

	/**
	 * Sleep time if no job is found (in seconds)
	 * @var int
	 */
	protected static $sleep_time = 10;

	/**
	 * Register Queue
	 * @throws \ReflectionException
	 */
	public static function register() {
		parent::register();

		$GLOBALS['mtt_queues'][(new \ReflectionClass(static::class))->getShortName()] = static::class;
	}

	/**
	 * Start QueueWorker
	 */
	public static function startQueueWorker() {
		set_time_limit(0);

		while(static::shouldRun()) {
			if(!static::handleNextJob()) {
				sleep(static::$sleep_time);
			}
		}
	}

	/**
	 * Fetch next job and handle it
	 * @return bool
	 */
	protected static function handleNextJob() {
		global $wpdb;
		$table_name = static::getTableName();
		$now = current_time('mysql');

		// Avoid mysql errors with DB gone
		if(!$wpdb->check_connection()) {
			return false;
		}

		$wpdb->query("LOCK TABLES $table_name WRITE");

		$job = get_called_class()::getFirst(array(
			'orderby' => 'priority, date',
			'order' => 'ASC',
			'where' => array(
				"run_date IS NULL",
				array(
					"date",
					"<=",
					$now,
				),
			)
		), false);

		if(!empty($job)) {
			$job->updateRunDate();
		}

		$wpdb->query("UNLOCK TABLES");

		if(empty($job)) {
			return false;
		}

		$job->handle(false);

		return true;
	}

	/**
	 * Shall the queue still run?
	 * @return bool
	 */
	protected static function shouldRun() {
		return true;
	}



	/**
	 * Create a new scheduled job
	 *
	 * @param mixed $callback   The callback to run when the job is to be executed.
	 *
	 * @param string $arg       The data to be passed to the callback
	 *
	 * @param date $date        mySQL formatted date for when to run the job
	 *
	 * @param int $priority     Jobs will be ordered by this ASC
	 *
	 * @return bool             true if job was successfully created
	 */
	public static function createJob($callback, $arg = NULL, $date = NULL, $priority = 10) {
		$job_props = static::prepareJob( $callback, $arg, $date, $priority );
		$class_name = get_called_class();
		$job = new $class_name($job_props);
		$status = $job->save();

		return $status;
	}

	protected static function prepareJob($callback, $arg = NULL, $date = NULL, $priority = 10) {
		$component = NULL;
		$date = (empty($date)) ? \current_time( 'mysql') : $date;

		if(is_array($callback)){
			$component = $callback[0];
			$callback = $callback[1];
		}
		$job_props = array(
			'date'      => $date,
			'callback'  => $callback,
			'component' => $component,
			'arg'       => $arg,
			'priority'  => $priority,
		);

		return $job_props;
	}


	// INSTANCE METHODS

	/**
	 * Handle job
	 */
	public function handle($update_run_date = true) {
		do_action('mtt/job_queue/handle_job/before', $this);
		if($update_run_date) {
			$this->updateRunDate();
		}
		$result = $this->doJob();
		$this->updateResult( $result );
		do_action('mtt/job_queue/handle_job/after', $this, $result);
	}

	/**
	 * Do the job
	 * @return false|mixed|string
	 */
	public function doJob() {
		if(!empty($this->component)){
			if(class_exists( $this->component)) {
				if(method_exists($this->component, $this->callback)) {
					return call_user_func_array(array($this->component,$this->callback), array($this->arg));
				}else{
					return json_encode(new \WP_Error('no_such_component_method', Project::translateReplace( 'Method "%s" not found for component "%s" in Job', array($this->callback,$this->component))));
				}
			}else{
				return json_encode(new \WP_Error('no_such_component', Project::translateReplace( 'Component "%s" not found for callback in Job', array($this->component))));
			}
		}else{
			if(function_exists($this->callback)) {
				return call_user_func_array($this->callback, array($this->arg));
			}else{
				return json_encode(new \WP_Error( 'no_such_callback_function', Project::translateReplace( 'Function "%s" not found for callback in Job', array($this->callback))));
			}
		}
	}

	/**
	 * Update run date
	 */
	public function updateRunDate() {
		$this->run_date = \current_time('mysql');
		$this->save();
	}

	/**
	 * Update result
	 * @param $result
	 */
	public function updateResult($result) {
		$this->result = $result;
		$this->save();
	}
}