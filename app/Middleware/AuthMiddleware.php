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
 * Only allows authenticated users to accesss certain routes
 */
class AuthMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if (!$this->container->auth->check()) {
			$this->container->flash->addMessage('warning', '<b>Uhh...</b> Please sign in before doing that.');
			return $response->withRedirect($this->container->router->pathFor('auth.signin') . "?redirect=" . $request->getUri()->getPath());
		}

		$response = $next($request, $response);
		return $response;
	}
}
