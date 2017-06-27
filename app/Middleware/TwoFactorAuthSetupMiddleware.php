<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Only allows access if the user hasn't set up 2fa
 */
class TwoFactorAuthSetupMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if ($this->container->auth->user()->settings->tfa_enabled) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> You already have two-factor auth setup! Disable and re-enable it to set it up again.');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('user.profile.2fa'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
