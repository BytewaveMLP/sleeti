<?php

use Eeti\Middleware\AuthMiddleware;
use Eeti\Middleware\GuestMiddleware;
use Eeti\Middleware\TesterMiddleware;
use Eeti\Middleware\ModeratorMiddleware;
use Eeti\Middleware\AdminMiddleware;

$app->get('/', 'HomeController:index')->setName('home');

$app->group('', function() {
	$this->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');
	$this->post('/auth/signup', 'AuthController:postSignUp');

	$this->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');
	$this->post('/auth/signin', 'AuthController:postSignIn');
})->add(new GuestMiddleware($container));

$app->group('', function() use ($container) {
	$this->get('/auth/signout', 'AuthController:getSignOut')->setName('auth.signout');

	$this->get('/auth/password/change', 'PasswordController:getChangePassword')->setName('auth.password.change');
	$this->post('/auth/password/change', 'PasswordController:postChangePassword');

	$this->group('', function() {
		$this->get('/admin/acp', 'AdminController:getAcp')->setName('admin.acp');
		$this->post('/admin/acp', 'AdminController:postAcp');
	})->add(new AdminMiddleware($container));
})->add(new AuthMiddleware($container));
