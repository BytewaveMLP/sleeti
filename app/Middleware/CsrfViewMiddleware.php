<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Prevents CSRF/XSRF attacks
 */
class CsrfViewMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$this->container->view->getEnvironment()->addGlobal('csrf', [
			'field' => '
				<input type="hidden" name="' . $this->container->csrf->getTokenNameKey() . '" value="' . $this->container->csrf->getTokenName() . '">
				<input type="hidden" name="' . $this->container->csrf->getTokenValueKey() . '" value="' . $this->container->csrf->getTokenValue() . '">
			',
		]);

		$response = $next($request, $response);
		return $response;
	}
}
