<?php

use \Respect\Validation\Validator as v;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;

session_start();

require __DIR__ . '/../vendor/autoload.php';

// Make sure config.json actually exists - a lot breaks if it doesn't
if (!file_exists(__DIR__ . '/../config/config.json')) {
	if (!is_dir(__DIR__ . '/../config/')) { // make sure /config/ even exists (fixes .gitignore issue)
		mkdir(__DIR__ . '/../config');
	}

	file_put_contents(__DIR__ . '/../config/config.json', '{}');
}

$settings['settings'] = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);

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
	return new \Sleeti\Auth\Auth($container);
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
	return new \Sleeti\Validation\Validator;
};

require __DIR__ . '/registercontrollers.php';

$container['csrf'] = function ($container) {
	return new \Slim\Csrf\Guard;
};

$app->add(new \Sleeti\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \Sleeti\Middleware\OldInputMiddleware($container));

v::with('Sleeti\\Validation\\Rules');

require __DIR__ . '/../app/routes.php';
