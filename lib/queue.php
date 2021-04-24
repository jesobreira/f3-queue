<?php

class Queue extends \Prefab {

	/**
	 * Interval which we should check for jobs (in ms)
	 */
	public $interval = 100;

	/**
	 * Log file
	 */
	public $log = null;

	/**
	 *  Path to PHP binary
	 */
	public $binary = 'php';

	/**
	 * ActiveRecord instance to use
	 */
	public $db = \Base::instance()->get('DB');

	/**
	 * Stores a queue's default settings
	 */
	protected $default_config = [
		'attempts' => 1,
		'priority' => 0,
		'timeout' => 0
	];

	/**
	 * Stores all queues
	 */
	$queues = [];

	/**
	 * Creates a new queue
	 */
	public function set($job_name, $callable, $config = []) {
		$config = array_merge($this->default_config, $config);

		$this->queues[$job_name] = new stdClass();
		$this->queues[$job_name]->callable = $callable;
		$this->queues[$job_name]->config = $config;

		return true;
	}

	/**
	 * Dispatches an execution
	 */
	public function dispatch($job_name, $params = [], $config = []) {
		// does this queue exist?
		if (!array_key_exists($job_name, $this->queues)) {
			trigger_error('Queue '.$job_name.' does not exist', E_USER_ERROR);
			return false;
		}

		// serializes params
		$args = sizeof($params) ? serialize($params) : null;

		// get task config
		$config = array_merge($this->default_config, $this->queues[$job_name]->config, $config);

		// registers execution

	}

	public function _force_cli() {
		// this can only run from CLI
		if (PHP_SAPI !== 'cli') {
            $f3->error(404);
            exit;
		}
	}

	public function _log($msg) {
		if ($this->log) {
			$log = new \Log($this->log);
			$log->write(date('Y-m-d H:i:s') . ': ' . $msg);
		}
	}

	/**
	 * Daemon
	 */
	public function _daemon($f3, $params) {
		$this->force_cli();

		// should we limit this instance to some queues?
		$list = array_key_exists('list', $params) ? explode(",", $params['list']) : [];

		$running_jobs = [];

		// main loop
		while (true) {
			// gets next job
			$job = 1; // TO-DO

			// mark job as executing
			// TO-DO

			// execute job
			$this->_launch($job->queue, $job->params);

			// sleeps
			usleep($this->interval * 1000);
		}
	}

	/**
	 * Tasks launcher
	 */
	public function _launch($queue, $params = []) {
		$this->force_cli();

		$params = urlencode(serialize($params));

		// PHP docs: If a program is started with this function, in order for it to continue running in the background,
        // the output of the program must be redirected to a file or another output stream.
        // Failing to do so will cause PHP to hang until the execution of the program ends.
        $dir = dirname($this->script);
        $file = basename($this->script);

        $is_windows = (bool)preg_match('/^win/i',PHP_OS);

        if (!preg_match($is_windows ? '/^[A-Z]:\\\\/i':'/^\//',$dir)) {
            $dir = getcwd().'/'.$dir;
        }

        if ($is_windows) {
            pclose(popen(sprintf('start /b "cron" "%s" "%s\\%s" "/run/%s/%s"', $this->binary, $dir, $file, $queue, $params), 'r'));
        } else {
            exec(sprintf('cd "%s" && %s %s /f3queue/run/%s/%s >/dev/null 2>/dev/null &', $dir, $this->binary, $file, $queue, $params));
        }
	}

	/**
	 * Tasks runner
	 */
	public _run($f3, $params) {
		$this->force_cli();

		$queue = $params['queue'];

		$params = array_key_exists('params', $params) ? unserialize($params) : [];

		// executes and gets return code
		$ret_code = call_user_func_array($this->queues[$queue]->callable, $params);

		// if the function returns a boolean, convert true to 0 and false to non-zero
		if ($ret_code === true) {
			$ret_code = 0;
		} elseif ($ret_code === false) {
			$ret_code = 1;
		} else {
			$ret_code = (int)$ret_code;
		}

		// registers job execution
		// TO-DO
	}

	/**
	 * Class constructor
	 * Will also register some magic routes
	 */
	public function __construct() {
		$f3 = \Base::instance();

		$f3->route(['GET /f3queue [cli]', 'GET /f3queue/@list [cli]'], [$this, '_daemon']);
		$f3->route (['GET /f3queue/run/@queue [cli]', 'GET /f3queue/run/@queue/@params [cli]'], [$this, '_run']);

		// get queues from variable
		$config = (array)$f3->get('QUEUES');

		$default_cfg = [];

		if (array_key_exists('attempts', $config)]) {
			$default_cfg['attempts'] = $config['attempts'];
		}

		if (array_key_exists('priority', $config)]) {
			$default_cfg['priority'] = $config['priority'];
		}

		if (array_key_exists('timeout', $config)]) {
			$default_cfg['timeout'] = $config['timeout'];
		}

		$this->default_config = array_merge($this->default_config, $default_cfg);

		// load jobs from list
		if (array_key_exists('jobs', $config) && is_array($config['jobs'])) {
			foreach ($config['jobs'] as $job) {
				
			}
		}
	}
}