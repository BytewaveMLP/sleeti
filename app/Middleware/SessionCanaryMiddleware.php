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
