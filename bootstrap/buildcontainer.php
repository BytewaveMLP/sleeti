<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
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
		'check'    => $container->auth->check(),
		'user'     => $container->auth->user(),
	]);

	$view->getEnvironment()->addGlobal('base_url', $container->request->getUri()->getBaseUrl());

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

$container['mail'] = function ($container) {
	$mailgun = new \Mailgun\Mailgun($container['settings']['mail']['apikey'], new \Http\Adapter\Guzzle6\Client());
	return new \Sleeti\Mail\Mailer($container, $mailgun);
};
