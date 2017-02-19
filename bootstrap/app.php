<?php

/**
 * This file is part of sleeti.
 * Copyright (C) 2016  Eliot Partridge
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
