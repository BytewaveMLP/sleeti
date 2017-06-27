<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Logs all page views to debug channel
 */
class LogPageViewMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$path = $request->getUri()->getPath();
		if ($this->container->auth->check()) {
			$user   = $this->container->auth->user();
			$viewer = $user->username . ' (' . $user->id . ')';
		} else {
			$viewer = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
		}

		$this->container->log->debug('pageview', 'Pageview from ' . $viewer . ' (' . $path . ').');

		$response = $next($request, $response);
		return $response;
	}
}
