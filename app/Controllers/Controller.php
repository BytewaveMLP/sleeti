<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers;

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
