<?php

namespace Eeti\Middleware;

/**
 * Only allows access if the eeti slim instance isn't fully installed
 */
class NotInstalledMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if (file_exists(__DIR__ . '/../../config/lock')) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> This instance of eeti slim is already configured!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
