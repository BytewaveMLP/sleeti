<?php

namespace Sleeti\Middleware;

/**
 * Easy dependecy injection for all Middleware
 */
class Middleware
{
	protected $container;

	public function __construct($container) {
		$this->container = $container;
	}
}
