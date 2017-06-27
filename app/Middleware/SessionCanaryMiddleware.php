<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;


/**
 * Regenerate session IDs after a certain time interval.
 * Taken from https://paragonie.com/blog/2015/04/fast-track-safe-and-secure-php-sessions
 */
class SessionCanaryMiddleware extends Middleware
{
	const TIMEOUT = 300; // timeout in seconds (default: 5 minutes)

	public function __invoke($request, $response, $next) {
		// If there is no session canary, regenerate our session ID and set one
		if (!isset($_SESSION['canary'])) {
			session_regenerate_id(true);
			$_SESSION['canary'] = time();
		}

		// If there is one, make sure it's not expired
		if ($_SESSION['canary'] < time() - self::TIMEOUT) {
			session_regenerate_id(true);
			$_SESSION['canary'] = time();
		}

		$response = $next($request, $response);
		return $response;
	}
}
