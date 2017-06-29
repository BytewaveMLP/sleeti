<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers\Auth;

use \Sleeti\Controllers\Controller;
use \Sleeti\Models\User;
use \Sleeti\Models\UserPasswordRecoveryToken;
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
			'password' => password_hash($request->getParam('password_new'), PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10]),
		]);

		$this->container->log->info('auth', $user->username . ' (' . $user->id . ') changed their password.');

		// Invalidate all remember tokens for security
		$this->container->auth->removeAllRememberCredentials();
		$this->container->auth->signout();

		$this->container->flash->addMessage('success', '<b>Yay!</b> Your password has been updated successfully.');
		$this->container->flash->addMessage('info', 'For security reasons, you will need to log in again on all your devices.');

		return $response->withRedirect($this->container->router->pathFor('home'));
	}

	public function getForgotPassword($request, $response) {
		return $this->container->view->render($response, 'auth/password/forgot.twig');
	}

	public function postForgotPassword($request, $response) {
		$validation = $this->container->validator->validate($request, [
			'email' => v::notEmpty()->email(),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like we\'re missing something...');
			return $response->withRedirect($this->container->router->pathFor('auth.password.forgot'));
		}

		$email = $request->getParam('email');

		$user = User::where('email', $email);

		if ($user->count() != 0) {
			$user = $user->first();

			$rand = $this->container->randomlib;

			$identifier = $rand->generateString(255);
			$token      = $rand->generateString(255);
			$tokenHash  = hash('sha384', $token);

			$recoveryToken = UserPasswordRecoveryToken::create([
				'user_id'    => $user->id,
				'identifier' => $identifier,
				'token'      => $tokenHash,
				'expires'    => date('Y-m-d H:i:s', strtotime('+1 hour')),
			]);

			$this->container->mail->send('email/password-reset.twig', [
				'identifier' => urlencode($identifier),
				'token'      => urlencode($token),
			], function($message) use ($email) {
				$message->to($email);
				$message->subject('Reset your ' . ($this->container['settings']['site']['title'] ?? 'sleeti') . ' password');
			});
		}

		$this->container->flash->addMessage('success', 'Further instructions have been sent to ' . $email);
		$this->container->flash->addMessage('info', '<b>NOTE:</b> If you don\'t see our email, <b>check your spam folder</b>! Also, please ensure you entered the correct email above.');
		return $response->withRedirect($this->container->router->pathFor('home'));
	}

	private function getRecoveryToken($identifier, $token) {
		if (!$identifier || !$token) {
			return null;
		}

		$tokenHash = hash('sha384', $token);

		$dbTokens = UserPasswordRecoveryToken::where('identifier', $identifier);

		if ($dbTokens->count() > 0) {
			$dbTokens = $dbTokens->get();

			foreach ($dbTokens as $dbToken) {
				if (hash_equals($tokenHash, $dbToken->token)) {
					if (strtotime($dbToken->expires) > time()) {
						return $dbToken;
					} else {
						return null;
					}
				}
			}
		}

		return null;
	}

	public function getResetPassword($request, $response) {
		$identifier = $request->getParam('identifier');
		$token      = $request->getParam('token');
		
		if (!$this->getRecoveryToken($identifier, $token)) {
			$this->container->flash->addMessage('danger', 'Invalid or missing password recovery token!');
			return $response->withRedirect($this->container->router->pathFor('home'))->withStatus(403);
		}

		return $this->container->view->render($response, 'auth/password/reset.twig', [
			'identifier' => $identifier,
			'token'      => $token,
		]);
	}

	public function postResetPassword($request, $response) {
		$identifier = $request->getParam('identifier');
		$token      = $request->getParam('token');

		$dbToken = $this->getRecoveryToken($identifier, $token);

		if (!$dbToken) {
			$this->container->flash->addMessage('danger', 'Invalid or missing password recovery token!');
			return $response->withRedirect($this->container->router->pathFor('home'))->withStatus(403);
		}

		$validation = $this->container->validator->validate($request, [
			'password'         => v::notEmpty(),
			'password_confirm' => v::passwordConfirmation($request->getParam('password')),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> Something went wrong.');
			return $response->withRedirect($this->container->router->pathFor('auth.password.reset') . '?identifier=' . urlencode($identifier) . '&token=' . urlencode($token));
		}

		$user = $dbToken->user;

		$user->update([
			'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10]),
		]);

		$this->container->log->info('auth', $user->username . ' (' . $user->id . ') reset their password.');

		foreach ($user->rememberTokens as $token) {
			$token->delete();
		}

		foreach ($user->passwordRecoveryTokens as $token) {
			$token->delete();
		}

		$this->container->flash->addMessage('success', '<b>Yay!</b> Your password has been reset successfully.');
		$this->container->flash->addMessage('info', 'For security reasons, you will need to log in again on all your devices.');

		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
