<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Attempts to authenticate users if they have a remember me cookie
 */
class RememberMeMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$this->container->auth->attemptRemember();

		$response = $next($request, $response);
		return $response;
	}
}
