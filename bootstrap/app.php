<?php

use \Respect\Validation\Validator as v;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;

session_start();

require __DIR__ . '/../vendor/autoload.php';

// need to fix this config at some point
$app = new \Slim\App([
	'settings' => [
		'displayErrorDetails' => true,
		'db' => [
			'driver'    => 'mysql',
			'host'      => 'localhost',
			'database'  => 'homestead',
			'username'  => 'homestead',
			'password'  => 'secret',
			'charset'   => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci',
			'prefix'    => '',
		],
		'password' => [
			'cost' => 10,
		],
		'upload' => [
			'path' => '/home/vagrant/uploads/',
		],
	],
]);

$container = $app->getContainer();

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

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

	return $view;
};

$container['validator'] = function ($container) {
	return new \Eeti\Validation\Validator;
};

$container['HomeController'] = function ($container) {
	return new \Eeti\Controllers\HomeController($container);
};

$container['AuthController'] = function ($container) {
	return new \Eeti\Controllers\Auth\AuthController($container);
};

$container['PasswordController'] = function ($container) {
	return new \Eeti\Controllers\Auth\PasswordController($container);
};

$container['AdminController'] = function ($container) {
	return new \Eeti\Controllers\AdminController($container);
};

$container['FileController'] = function ($container) {
	return new \Eeti\Controllers\FileController($container);
};

$container['ProfileController'] = function ($container) {
	return new \Eeti\Controllers\ProfileController($container);
};

$container['csrf'] = function ($container) {
	return new \Slim\Csrf\Guard;
};

$app->add(new \Eeti\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \Eeti\Middleware\OldInputMiddleware($container));

v::with('Eeti\\Validation\\Rules');

require __DIR__ . '/../app/routes.php';
