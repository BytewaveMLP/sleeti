<?php

namespace Eeti\Middleware;

class TesterMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		if (!$this->container->auth->user()->isTester()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> Only testers are allowed there!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$response = $next($request, $response);
		return $response;
	}
}
