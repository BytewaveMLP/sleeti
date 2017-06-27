<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Middleware;

/**
 * Only allows Moderators access to certain routes
 */
class ModeratorMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$user = $this->container->auth->user();

		if (!$user->isModerator()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> Only moderators and admins are allowed there!');

			$this->container->log->warning('mod', $user->username . ' (' . $user->id . ') user attempted to access mod-only area.');

			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
