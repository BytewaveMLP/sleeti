<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

use \Respect\Validation\Validator as v;

// Set secure session INI settings
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');

session_start();

require __DIR__ . '/../vendor/autoload.php';

// Make sure config.json actually exists - a lot breaks if it doesn't
if (!file_exists(__DIR__ . '/../config/config.json')) {
	if (!is_dir(__DIR__ . '/../config/')) { // make sure /config/ even exists (fixes .gitignore issue)
		mkdir(__DIR__ . '/../config');
	}

	file_put_contents(__DIR__ . '/../config/config.json', '{}'); // Hacky hack hack hack hack
}

// Load the config
$settings['settings'] = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);

$settings['settings']['determineRouteBeforeAppMiddleware'] = true;

// Load up Slim...
$app = new \Slim\App($settings);

// ... and get its container object
$container = $app->getContainer();

// Create database connection
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db'] ?? []);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Build up Slim's container and middleware
require __DIR__ . '/buildcontainer.php';
require __DIR__ . '/registercontrollers.php';
require __DIR__ . '/errorhandlers.php';
require __DIR__ . '/globalmiddleware.php';

// Initialize Respect\Validation with our custom validation rules
v::with('Sleeti\\Validation\\Rules');

// Load our routes
require __DIR__ . '/../app/routes.php';
