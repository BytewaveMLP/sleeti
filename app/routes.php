<?php

use Eeti\Middleware\AuthMiddleware;
use Eeti\Middleware\GuestMiddleware;
use Eeti\Middleware\TesterMiddleware;
use Eeti\Middleware\ModeratorMiddleware;
use Eeti\Middleware\AdminMiddleware;
use Eeti\Middleware\CsrfViewMiddleware;

$app->get('/', 'HomeController:index')->setName('home');

$app->get('/user/{id}', 'ProfileController:viewProfile')->setName('user.profile');

$app->get('/viewfile/{filename}', 'FileController:viewFile')->setName('file.view');

$app->group('', function() use ($container) { // it's groups all the way down
	$this->group('', function() use ($container) {
		$this->group('', function() use ($container) {
			$this->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');
			$this->post('/auth/signup', 'AuthController:postSignUp');

			$this->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');
			$this->post('/auth/signin', 'AuthController:postSignIn');
		})->add(new GuestMiddleware($container));

		$this->group('', function() use ($container) {
			$this->get('/auth/signout', 'AuthController:getSignOut')->setName('auth.signout');

			$this->get('/auth/password/change', 'PasswordController:getChangePassword')->setName('auth.password.change');
			$this->post('/auth/password/change', 'PasswordController:postChangePassword');

			$this->get('/editprofile', 'ProfileController:getEditProfile')->setName('user.profile.edit');
			$this->post('/editprofile', 'ProfileController:postEditoProfile');

			$this->get('/upload', 'FileController:getUpload')->setName('file.upload');
			$this->post('/upload', 'FileController:postUpload');

			$this->group('', function() {
				$this->get('/admin/acp', 'AdminController:getAcp')->setName('admin.acp');
				$this->post('/admin/acp', 'AdminController:postAcp');
			})->add(new AdminMiddleware($container));
		})->add(new AuthMiddleware($container));
	})->add(new CsrfViewMiddleware($container));
})->add($container['csrf']);

$app->post('/upload/sharex', 'FileController:sharexUpload');
