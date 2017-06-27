<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Only allows access if the sleeti instance isn't fully installed
 */
class NotInstalledMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if (file_exists(__DIR__ . '/../../config/lock')) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> This instance of sleeti is already configured!');

			if ($this->container->auth->check()) {
				$user   = $this->container->auth->user();
				$viewer = $user->username . ' (' . $user->id . ')';
			} else {
				$viewer = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
			}

			$this->container->log->warning('install', $viewer . ' tried to access the install page when Sleeti was already installed.');

			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
