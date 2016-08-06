<?php

namespace Eeti\Controllers\Auth;

use Eeti\Controllers\Controller;
use Eeti\Models\User;
use Respect\Validation\Validator as v;

class PasswordController extends Controller
{
	public function getChangePassword($request, $response) {
		return $this->container->view->render($response, 'auth/password/change.twig');
	}

	public function postChangePassword($request, $response) {
		$validation = $this->container->validator->validate($request, [
			'password_old' => v::notEmpty()->matchesPassword($this->container->auth->user()->password),
			'password'     => v::notEmpty(),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> Something went wrong.');
			return $response->withRedirect($this->container->router->pathFor('auth.password.change'));
		}

		$this->container->auth->user()->update([
			'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10]),
		]);

		$this->container->flash->addMessage('success', '<b>Yay!</b> Your password has been updated successfully.');
		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
