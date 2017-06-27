<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Only allows partially authenticated users to accesss certain routes
 */
class TwoFactorAuthPartialMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if (!$this->container->auth->user()) {
			return $response->withRedirect($this->container->router->pathFor('auth.signin'));
		} elseif ($this->container->auth->user()->settings->tfa_enabled && !isset($_SESSION['tfa-partial'])) {
			$this->container->flash->addMessage('warning', '<b>Hey!</b> You\'re already signed in!');
			return $response->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
