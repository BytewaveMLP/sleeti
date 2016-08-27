<?php

use \Respect\Validation\Validator as v;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;

session_start();

require __DIR__ . '/../vendor/autoload.php';

// need to fix this config at some point

$settings = [
	'settings' => [
		'displayErrorDetails' => true,
	],
];

// Make sure config.json actually exists - a lot breaks if it doesn't
if (!file_exists(__DIR__ . '/../config/config.json')) {
	file_put_contents(__DIR__ . '/../config/config.json', '{}');
}

$decodedConfig = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);

// Merge config.json with current app settings
// TODO: don't do this, move displayErrorDetails to ACP
$settings['settings'] = array_merge($settings['settings'], $decodedConfig);

$app = new \Slim\App($settings);

$container = $app->getContainer();

// Create database connection
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db'] ?? []);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Save decoded config.json for modification through ACP (nasty hack, I know :()
$container['config'] = function($container) use ($settings) {
	return $settings['settings'];
};

$container['db'] = function($container) use ($capsule) {
	return $capsule;
};

$container['auth'] = function ($container) {
	return new \Eeti\Auth\Auth;
};

$container['flash'] = function ($container) {
	return new \Slim\Flash\Messages;
};

$container['view'] = function ($container) {
	$view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
		'cache' => false,
	]);

	$view->addExtension(new \Slim\Views\TwigExtension(
		$container->router,
		$container->request->getUri()
	));

	$markdownEngine = new MarkdownEngine\ParsedownEngine;

	$view->addExtension(new MarkdownExtension($markdownEngine));

	$view->getEnvironment()->addGlobal('auth', [
		'check' => $container->auth->check(),
		'user'  => $container->auth->user(),
	]);

	$view->getEnvironment()->addGlobal('flash', $container->flash);

	$view->getEnvironment()->addGlobal('settings', $container->settings);

	return $view;
};

$container['validator'] = function ($container) {
	return new \Eeti\Validation\Validator;
};

require __DIR__ . '/registercontrollers.php';

$container['csrf'] = function ($container) {
	return new \Slim\Csrf\Guard;
};

$app->add(new \Eeti\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \Eeti\Middleware\OldInputMiddleware($container));

v::with('Eeti\\Validation\\Rules');

require __DIR__ . '/../app/routes.php';
