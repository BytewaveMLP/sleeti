<?php

namespace Eeti\Middleware;

class OldInputMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$this->container->view->getEnvironment()->addGlobal('old', $_SESSION['old'] ?? null);
		$_SESSION['old'] = $request->getParams();

		$response = $next($request, $response);
		return $response;
	}
}
