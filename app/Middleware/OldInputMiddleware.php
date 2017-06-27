<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Persists old input for forms that request it
 */
class OldInputMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$this->container->view->getEnvironment()->addGlobal('old', $_SESSION['old'] ?? null);
		$_SESSION['old'] = $request->getParams();

		$response = $next($request, $response);
		return $response;
	}
}
