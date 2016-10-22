<?php

namespace Sleeti\Logging;

class Logger {
	protected $container;

	protected $handler;

	protected $loggers;

	public function __construct($container) {
		$this->container = $container;

		if (!is_dir($this->container['settings']['logging']['path']) && $this->container['settings']['logging']['enabled']) {
			mkdir($this->container['settings']['logging']['path']);
		}

		$logfile       = $this->container['settings']['logging']['path'] . 'sleeti.log';
		$this->handler = new \Monolog\Handler\RotatingFileHandler($logfile, $container['settings']['logging']['maxFiles'] ?? 0);

		$dateFormat   = 'H:i:s';
		$outputFormat = "[%datetime%] [%level_name%] %channel%: %message% %context%\n";
		$formatter    = new \Monolog\Formatter\LineFormatter($outputFormat, $dateFormat);

		$this->handler->setFormatter($formatter);
	}

	private function addLogger($name) {
		$logger = new \Monolog\Logger($name);
		$logger->pushHandler($this->handler, $this->container['settings']['logging']['level'] ?? \Monolog\Logger::INFO);
		$this->loggers[$name] = $logger;
	}

	public function log($name, $level, $message, array $context = []) {
		if (!$this->container['settings']['logging']['enabled']) return;

		if (!isset($loggers[$name])) {
			$this->addLogger($name);
		}

		$this->loggers[$name]->log($level, $message, $context);
	}
}
