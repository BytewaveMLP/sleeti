<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers\Auth;

use \Sleeti\Controllers\Controller;
use \Sleeti\Models\User;
use \Respect\Validation\Validator as v;

class PasswordController extends Controller
{
	public function getChangePassword($request, $response) {
		return $this->container->view->render($response, 'auth/password/change.twig');
	}

	public function postChangePassword($request, $response) {
		$validation = $this->container->validator->validate($request, [
			'password'             => v::notEmpty()->matchesPassword($this->container->auth->user()->password),
			'password_new'         => v::notEmpty(),
			'password_new_confirm' => v::passwordConfirmation($request->getParam('password_new')),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> Something went wrong.');
			return $response->withRedirect($this->container->router->pathFor('auth.password.change'));
		}

		$user = $this->container->auth->user();

		$user->update([
			'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10]),
		]);

		$this->container->log->info('auth', $user->username . ' (' . $user->id . ') changed their password.');

		// Invalidate all remember tokens for security
		$this->container->auth->removeAllRememberCredentials();
		$this->container->auth->signout();

		$this->container->flash->addMessage('success', '<b>Yay!</b> Your password has been updated successfully.');
		$this->container->flash->addMessage('info', 'For security reasons, you will need to log in again on all your devices.');

		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
