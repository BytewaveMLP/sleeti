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

namespace Sleeti\Controllers\Auth;

use Sleeti\Controllers\Controller;
use Sleeti\Models\User;
use Respect\Validation\Validator as v;

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
