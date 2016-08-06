<?php

namespace Eeti\Controllers\Auth;

use Eeti\Controllers\Controller;
use Eeti\Models\User;
use Respect\Validation\Validator as v;

class AuthController extends Controller
{
	public function getSignIn($request, $response) {
		return $this->container->view->render($response, 'auth/signin.twig');
	}

	public function postSignIn($request, $response) {
		$auth = $this->container->auth->attempt(
			$request->getParam('identifier'),
			$request->getParam('password')
		);

		if (!$auth) {
			return $response->withRedirect($this->container->router->pathFor('auth.signin'));
		}

		return $response->withRedirect($this->container->router->pathFor('home'));
	}

	public function getSignUp($request, $response) {
		return $this->container->view->render($response, 'auth/signup.twig');
	}

	public function postSignUp($request, $response) {
		$validation = $this->container->validator->validate($request, [
			'email'    => v::notEmpty()->noWhitespace()->email()->emailAvailable(),
			'username' => v::notEmpty()->alnum('-_')->noWhitespace()->usernameAvailable(),
			'password' => v::notEmpty(),
		]);

		if ($validation->failed()) {
			return $response->withRedirect($this->container->router->pathFor('auth.signup'));
		}

		$user = User::create([
			'email'    => $request->getParam('email'),
			'username' => $request->getParam('username'),
			'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10]),
		]);

		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
