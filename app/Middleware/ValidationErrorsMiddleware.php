<?php

namespace Sleeti\Middleware;

/**
 * Perists validation errors across redirects
 */
class ValidationErrorsMiddleware extends Middleware
{
	public function __invoke($request, $response, $next) {
		$this->container->view->getEnvironment()->addGlobal('errors', $_SESSION['errors'] ?? null);
		unset($_SESSION['errors']);

		$response = $next($request, $response);
		return $response;
	}
}
