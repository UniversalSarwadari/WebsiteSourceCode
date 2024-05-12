<?php namespace Morningtrain\WooAdvancedQTY\Lib\Modules\JobQueue\Commands;

use Morningtrain\WooAdvancedQTY\Lib\Abstracts\CLICommand;

class QueueWorker extends CLICommand {

	protected static $command = 'queue';

	/**
	 * Starts a queue worker
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Class name of the queue (standard Job)
	 *
	 * ## EXAMPLES
	 *      wp queue start Job
	 *
	 * @after_wp_load
	 */
	public function start($args, $assoc_args) {
		\WP_CLI::log("Starting queue worker: {$args[0]}");

		if(!isset($GLOBALS['mtt_queues'][$args[0]]) || !class_exists($GLOBALS['mtt_queues'][$args[0]])) {
			\WP_CLI::error('Queue does not exist');
		}

		$class_name = $GLOBALS['mtt_queues'][$args[0]];

		$class_name::startQueueWorker();
	}

	/**
	 * Run single job
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Class name of the queue
	 *
	 * <id>
	 * : ID (Primary Key) on the job you will run
	 *
	 * [--untouched]
	 * : Whether or not to set run date on the job
	 *
	 * [--force]
	 * : Whether to force the job to run or not even though it has been running before
	 *
	 * ## EXAMPLES
	 *      wp queue run Job 101 --untouched --force
	 */
	public function run($args, $assoc_args) {
		\WP_CLI::log("Running job {$args[1]} in queue worker {$args[0]}");

		if(!isset($GLOBALS['mtt_queues'][$args[0]]) || !class_exists($GLOBALS['mtt_queues'][$args[0]])) {
			return \WP_CLI::error('Queue does not exist');
		}

		$class_name = $GLOBALS['mtt_queues'][$args[0]];

		$job = $class_name::getByPrimaryKey($args[1]);

		if($job === null) {
			\WP_CLI::error('Job does not exist');
		}

		if($job->run_date !== null && !isset($assoc_args['force'])) {
			\WP_CLI::error("Job has already been running with result: {$job->result}");
		}

		return $job->handle(!isset($assoc_args['untouched']));
	}
}