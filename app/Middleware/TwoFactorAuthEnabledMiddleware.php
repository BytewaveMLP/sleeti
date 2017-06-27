<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Only allows users with enabled (but not setup) 2FA to accesss certain routes
 */
class TwoFactorAuthEnabledMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if ($this->container->auth->user()->settings->tfa_secret === null) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> You need to enable two-factor auth before you can set it up!');
			return $response->withRedirect($this->container->router->pathFor('user.profile.2fa'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
