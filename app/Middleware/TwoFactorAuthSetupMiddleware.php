<?php

/**
 * This file is part of sleeti.
 * Copyright (C) 2016  Eliot Partridge
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sleeti\Middleware;

/**
 * Only allows access if the sleeti instance isn't fully installed
 */
class TwoFactorAuthSetupMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if ($this->container->auth->user()->tfa_enabled) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> You already have two-factor auth setup! Disable and re-enable it to set it up again.');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('user.profile.2fa'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
