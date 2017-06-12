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

// Save decoded config.json for modification through ACP (nasty hack, I know :()
$container['config'] = function($container) use ($settings) {
	return $settings['settings'];
};

// Globally give us DB access
$container['db'] = function($container) use ($capsule) {
	return $capsule;
};

// Give us access to our auth handler
$container['auth'] = function ($container) {
	return new \Sleeti\Auth\Auth($container);
};

// Slim\Flash messages
$container['flash'] = function ($container) {
	return new \Slim\Flash\Messages;
};

// Initialize Twig with custom extensions, views, and settings
$container['view'] = function ($container) {
	// Get our settings from our config
	$cacheEnabled    = $container['settings']['cache']['enabled'];
	$cachePath       = $container['settings']['cache']['path'];
	$cacheAutoReload = $container['settings']['cache']['auto_reload'];

	$view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
		'cache' => $cacheEnabled ? $cachePath : false,
		'auto_reload' => $cacheAutoReload,
	]);

	// Add Slim's Twig extension
	$view->addExtension(new \Slim\Views\TwigExtension(
		$container->router,
		$container->request->getUri()
	));

	// Create our Markdown parser...
	$markdownEngine = new \Sleeti\Twig\Markdown\SafeParsedownEngine;
	// ... and add it to Twig
	$view->addExtension(new \Aptoma\Twig\Extension\MarkdownExtension($markdownEngine));

	// Add our ReCaptcha extension
	$view->addExtension(new \Sleeti\Twig\Extensions\ReCaptchaExtension($container['settings']['recaptcha']['sitekey']));

	$view->addExtension(new \Sleeti\Twig\Extensions\FileHelperExtension);

	// Cache the values of auth->check() and auth->user() so we don't query the DB a bunch in views
	$view->getEnvironment()->addGlobal('auth', [
		'check' => $container->auth->check(),
		'user'  => $container->auth->user(),
	]);

	// Add access to the flash messages
	$view->getEnvironment()->addGlobal('flash', $container->flash);

	// Add our settings object for ACP forms
	$view->getEnvironment()->addGlobal('settings', $container->settings);

	return $view;
};

// Add Respect\Validation globally
$container['validator'] = function ($container) {
	return new \Sleeti\Validation\Validator;
};

// Add our CSRF guard to the container
$container['csrf'] = function ($container) {
	return new \Slim\Csrf\Guard;
};

// Add our two-factor authentication handler class
$container['tfa'] = function ($container) {
	return new \RobThree\Auth\TwoFactorAuth($container['settings']['site']['title'] ?? "sleeti");
};

// Add a medium-strength randomlib generator
$container['randomlib'] = function ($container) {
	$factory  = new \RandomLib\Factory;
	$strength = new \SecurityLib\Strength(\SecurityLib\Strength::MEDIUM);
	return $factory->getGenerator($strength);
};

// Add our logging handler
$container['log'] = function ($container) {
	return new \Sleeti\Logging\Logger($container);
};
