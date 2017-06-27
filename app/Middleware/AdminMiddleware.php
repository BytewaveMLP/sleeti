<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Only allows admins to access certain routes
 */
class AdminMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$user = $this->container->auth->user();

		if (!$user->isAdmin()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> Only admins are allowed there!');

			$this->container->log->warning('admin', $user->username . ' (' . $user->id . ') attempted to access admin-only area.');

			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
