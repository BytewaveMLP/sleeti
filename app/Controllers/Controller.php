<?php

namespace Eeti\Controllers;

/**
 * Controller base class
 *
 * Easy dependecy injection into all containers
 */
class Controller
{
	protected $container;

	public function __construct($container) {
		$this->container = $container;
	}
}
