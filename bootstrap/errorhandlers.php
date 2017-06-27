<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

// Register our custom 404 page
$container['notFoundHandler'] = function ($container) {
	return function ($request, $response) use ($container) {
		$response = $response->withStatus(404);
		return $container->view->render($response, 'errors/404.twig');
	};
};

// whoops! general error handling
$whoopsGuard = new \Zeuxisoo\Whoops\Provider\Slim\WhoopsGuard();
$whoopsGuard->setApp($app);
$whoopsGuard->setRequest($container['request']);
$whoopsGuard->setHandlers([]);
$whoopsGuard->install();
