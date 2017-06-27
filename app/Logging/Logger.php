<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Logging;

/**
 * Handles Sleeti logging
 */
class Logger
{
	/**
	 * Log levels constant array
	 * @var array
	 */
	const LOG_LEVELS = [
		'DEBUG'     => \Monolog\Logger::DEBUG,
		'INFO'      => \Monolog\Logger::INFO,
		'NOTICE'    => \Monolog\Logger::NOTICE,
		'WARNING'   => \Monolog\Logger::WARNING,
		'ERROR'     => \Monolog\Logger::ERROR,
		'CRITICAL'  => \Monolog\Logger::CRITICAL,
		'ALERT'     => \Monolog\Logger::ALERT,
		'EMERGENCY' => \Monolog\Logger::EMERGENCY,
	];

	/**
	 * Slim 3 container object
	 */
	protected $container;

	/**
	 * Log file handler
	 * @var \Monolog\Handler\RotatingFileHandler
	 */
	protected $handler;

	/**
	 * Array of cached Monolog logger instances
	 * @var array
	 */
	protected $loggers;

	public function __construct($container) {
		$this->container = $container;

		if (!is_dir($this->container['settings']['logging']['path']) && $this->container['settings']['logging']['enabled']) {
			mkdir($this->container['settings']['logging']['path']);
		}

		$logfile       = $this->container['settings']['logging']['path'] . 'sleeti.log';
		$this->handler = new \Monolog\Handler\RotatingFileHandler($logfile, $container['settings']['logging']['maxFiles'] ?? 0, $this->container['settings']['logging']['level'] ?? \Monolog\Logger::INFO);

		$dateFormat   = 'H:i:s';
		$outputFormat = "[%datetime%] [%channel%] [%level_name%]: %message%\n";
		$formatter    = new \Monolog\Formatter\LineFormatter($outputFormat, $dateFormat);

		$this->handler->setFormatter($formatter);
	}

	/**
	 * Adds a logger to the loggers cache
	 * @param string $name The name of the log channel this logger uses
	 */
	private function addLogger($name) {
		$logger = new \Monolog\Logger($name);
		$logger->pushHandler($this->handler);
		$this->loggers[$name] = $logger;
	}

	/**
	 * Logs to a specific logger channel
	 * @param  string $name    The logger channel to log to
	 * @param  int    $level   The logging level to output on
	 * @param  mixed  $message The log message to write
	 */
	public function log($name, $level, $message) {
		if (!$this->container['settings']['logging']['enabled']) return;

		if (!isset($loggers[$name])) {
			$this->addLogger($name);
		}

		$this->loggers[$name]->log($level, $message, []);
	}

	/**
	 * Shorthand for logging with the DEBUG level
	 * @param  string $name    The logger channel to log to
	 * @param  mixed  $message The log message to write
	 */
	public function debug($name, $message) {
		$this->log($name, \Monolog\Logger::DEBUG, $message);
	}

	/**
	 * Shorthand for logging with the INFO level
	 * @param  string $name    The logger channel to log to
	 * @param  mixed  $message The log message to write
	 */
	public function info($name, $message) {
		$this->log($name, \Monolog\Logger::INFO, $message);
	}

	/**
	 * Shorthand for logging with the NOTICE level
	 * @param  string $name    The logger channel to log to
	 * @param  mixed  $message The log message to write
	 */
	public function notice($name, $message) {
		$this->log($name, \Monolog\Logger::NOTICE, $message);
	}

	/**
	 * Shorthand for logging with the WARNING level
	 * @param  string $name    The logger channel to log to
	 * @param  mixed  $message The log message to write
	 */
	public function warning($name, $message) {
		$this->log($name, \Monolog\Logger::WARNING, $message);
	}

	/**
	 * Shorthand for logging with the ERROR level
	 * @param  string $name    The logger channel to log to
	 * @param  mixed  $message The log message to write
	 */
	public function error($name, $message) {
		$this->log($name, \Monolog\Logger::ERROR, $message);
	}

	/**
	 * Shorthand for logging with the CTRITICAL level
	 * @param  string $name    The logger channel to log to
	 * @param  mixed  $message The log message to write
	 */
	public function critical($name, $message) {
		$this->log($name, \Monolog\Logger::CRITICAL, $message);
	}

	/**
	 * Shorthand for logging with the ALERT level
	 * @param  string $name    The logger channel to log to
	 * @param  mixed  $message The log message to write
	 */
	public function alert($name, $message) {
		$this->log($name, \Monolog\Logger::ALERT, $message);
	}

	/**
	 * Shorthand for logging with the EMERGENCY level
	 * @param  string $name    The logger channel to log to
	 * @param  mixed  $message The log message to write
	 */
	public function emergency($name, $message) {
		$this->log($name, \Monolog\Logger::EMERGENCY, $message);
	}
}
