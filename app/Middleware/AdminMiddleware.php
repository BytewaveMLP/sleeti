<?php

namespace Eeti\Middleware;

/**
 * Only allows admins to access certain routes
 */
class AdminMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if (!$this->container->auth->user()->isAdmin()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> Only admins are allowed there!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
