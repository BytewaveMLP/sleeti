<?php

namespace Eeti\Middleware;

/**
 * Only allows authenticated users to accesss certain routes
 */
class AuthMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if (!$this->container->auth->check()) {
			$this->container->flash->addMessage('warning', '<b>Uhh...</b> Please sign in before doing that.');
			return $response->withRedirect($this->container->router->pathFor('auth.signin'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
