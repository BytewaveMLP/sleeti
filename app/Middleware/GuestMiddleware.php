<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Only allows unauthenticated users access to certain routes
 */
class GuestMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if ($this->container->auth->check()) {
			$this->container->flash->addMessage('warning', '<b>Uhh...</b> You\'re already logged into an account.');
			return $response->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
