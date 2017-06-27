<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Perists validation errors across redirects
 */
class ValidationErrorsMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$this->container->view->getEnvironment()->addGlobal('errors', $_SESSION['errors'] ?? null);
		unset($_SESSION['errors']);

		$response = $next($request, $response);
		return $response;
	}
}
