<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers;

class HomeController extends Controller
{
	public function index($request, $response) {
		return $this->container->view->render($response, 'home.twig');
	}

	public function robots($request, $response) {
		$response = $this->container->view->render($response, 'robots.twig');
		return $response->withStatus(200)->withHeader('Content-Type', 'text/plain');
	}
}
