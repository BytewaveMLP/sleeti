<?php

namespace Eeti\Middleware;

class Middleware
{
	protected $container;

	public function __construct($container) {
		$this->container = $container;
	}
}
