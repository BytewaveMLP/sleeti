<?php

namespace Eeti\Middleware;

class GuestMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if ($this->container->auth->check()) {
			$this->container->flash->addMessage('warning', '<b>Uhh...</b> You\'re already logged into an account.');
			return $response->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
