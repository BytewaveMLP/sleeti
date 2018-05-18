<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers\Auth;

use \Sleeti\Controllers\Controller;
use \Sleeti\Models\User;
use \Sleeti\Models\UserPermissions;
use \Sleeti\Models\UserSettings;
use \Respect\Validation\Validator as v;

class AuthController extends Controller
{
	public function getSignOut($request, $response) {
		$this->container->auth->signout();

		$this->container->flash->addMessage('info', 'You have been logged out.');
		return $response->withRedirect($this->container->router->pathFor('home'));
	}

	public function getSignIn($request, $response) {
		return $this->container->view->render($response, 'auth/signin.twig', [
			'redirect' => $request->getParam('redirect'),
		]);
	}

	public function postSignIn($request, $response) {
		$auth = $this->container->auth->attempt(
			$request->getParam('identifier'),
			$request->getParam('password')
		);

		$redirect = $request->getParam('redirect');

		if ($redirect == '') {
			$redirect = null;
		}

		if (!$auth) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> We couldn\'t find an account with those details.');
			return $response->withRedirect($this->container->router->pathFor('auth.signin') . ($redirect ? '?redirect=' . $redirect : ''));
		}

		$user = $this->container->auth->user();

		if ($user->settings->tfa_enabled) {
			$_SESSION['tfa-partial'] = true;
			return $response->withRedirect($this->container->router->pathFor('auth.signin.2fa') . ($redirect ? '?redirect=' . $redirect : '') . ($redirect ? '&' : '?') . 'remember=' . $request->getParam('remember'));
		}

		if ($request->getParam('remember') === "1") {
			$this->container->auth->updateRememberCredentials();
		}

		$this->container->flash->addMessage('success', '<b>Success!</b> Welcome back!');
		return $response->withRedirect($redirect ?? $this->container->router->pathFor('home'));
	}

	public function get2Fa($request, $response) {
		return $this->container->view->render($response, 'auth/2fa.twig', [
			'redirect' => $request->getParam('redirect'),
			'remember' => $request->getParam('remember'),
		]);
	}

	public function post2Fa($request, $response) {
		$user     = $this->container->auth->user();
		$tfa      = $this->container->tfa;
		$code     = $request->getParam('code');
		$redirect = $request->getParam('redirect');

		if ($redirect === '') {
			$redirect = null;
		}

		$validation = $this->container->validator->validate($request, [
			'tfa_code' => v::twoFactorAuthCode($tfa, $user),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like something isn\'t quite right here...');
			return $response->withRedirect($this->container->router->pathFor('auth.signin.2fa') . ($redirect ? '?redirect=' . $redirect : '') . ($redirect ? '&' : '?') . 'remember=' . $request->getParam('remember'));
		}

		unset($_SESSION['tfa-partial']);

		if ($request->getParam('remember') === "1") {
			$this->container->auth->updateRememberCredentials();
		}

		$this->container->flash->addMessage('success', '<b>Success!</b> Welcome back!');

		$this->container->log->info('auth', $user->username . ' (' . $user->id . ') finished logging in with 2FA.');

		return $response->withRedirect($redirect ?? $this->container->router->pathFor('home'));
	}

	public function get2FaCancel($request, $response) {
		unset($_SESSION['tfa-partial']);
		$this->container->auth->signout();

		return $response->withRedirect($this->container->router->pathFor('auth.signin'));
	}

	public function getSignUp($request, $response) {
		return $this->container->view->render($response, 'auth/signup.twig');
	}

	public function postSignUp($request, $response) {
		$rules = [
			'email'                => v::notEmpty()->noWhitespace()->email()->emailAvailable(),
			'username'             => v::notEmpty()->alnum('-_')->noTrailingWhitespace()->usernameAvailable(),
			'password'             => v::notEmpty(),
			'password_confirm'     => v::passwordConfirmation($request->getParam('password')),
		];

		if ($this->container['settings']['recaptcha']['enabled']) {
			$rules['g-recaptcha-response'] = v::reCaptcha($this->container['settings']['recaptcha']['secretkey']);
		}

		$validation = $this->container->validator->validate($request, $rules);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like something went wrong.');
			return $response->withRedirect($this->container->router->pathFor('auth.signup'));
		}

		$user = User::create([
			'email'    => $request->getParam('email'),
			'username' => $request->getParam('username'),
			'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10]),
		]);

		UserPermissions::create()->user()->associate($user)->save();
		UserSettings::create()->user()->associate($user)->save();

		$this->container->flash->addMessage('success', '<b>Success!</b> Welcome to ' . $this->container->settings['site']['title'] ?? 'sleeti' . '!');

		$this->container->log->info('auth', $user->username . ' (' . $user->id . ') signed up.');

		if ($user->id === 1) { // if this is the only user, give them admin
			$user->addPermission('A');
			$this->container->flash->addMessage('info', 'New administrative account created!');

			$this->container->log->notice('auth', 'Administrative account created.');
        }
        
        if ($this->container['settings']['mail']['enabled']) {
            $this->container->mail->send('email/account-created.twig', [
				'email' => $user->email,
			], function($message) use ($user) {
				$message->to($user->email);
				$message->subject('Account Created on ' . ($this->container['settings']['site']['title'] ?? 'sleeti'));
			});
        }
 
		$this->container->auth->attempt(
			$request->getParam('email'),
			$request->getParam('password')
		);

		return $response->withRedirect($this->container->router->pathFor('home'));
	}

	public function getDeleteAccount($request, $response, $args) {
		$args['id'] = $args['id'] ?? $this->container->auth->user()->id;

		$users = User::where('id', $args['id']);

		if ($users->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$user = $users->first();

		if ($this->container->auth->user()->id != $args['id'] && !$this->container->auth->user()->isAdmin()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> What do you think you\'re doing?! You can\'t delete someone else\'s account!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		return $this->container->view->render($response, 'user/delete.twig', [
			'id' => $args['id'] ?? $this->container->auth->user()->id,
			'user' => $user,
		]);
	}

	public function postDeleteAccount($request, $response, $args) {
		if ($this->container->auth->user()->id != $args['id'] && !$this->container->auth->user()->isAdmin()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> What do you think you\'re doing?! You can\'t delete someone else\'s account!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$users = User::where('id', $args['id']);

		if ($users->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$user = $users->first();

		$validation = $this->container->validator->validate($request, [
			'identifier' => v::MatchesUserIdentifier($user),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like we\'re missing something...');
			return $response->withRedirect($this->container->router->pathFor('user.profile.delete'));
		}

		foreach ($user->files as $file) {
			unlink($this->container['settings']['site']['upload']['path'] . $file->getPath());
			$file->delete();
		}

		$path = $this->container['settings']['site']['upload']['path'] . $user->id;

		if (is_dir($path)) {
			rmdir($path);
		}

		$authedUser = $this->container->auth->user();

		$this->container->log->info('auth', $user->username . ' (' . $user->id . ')\'s account was deleted by ' . $authedUser->username . ' (' . $authedUser->id . ').');

		if ($this->container->auth->user()->id == $args['id']) {
			$this->container->auth->signout();
		}

		$user->settings->delete();
		$user->permissions->delete();
		$user->delete();

		$this->container->flash->addMessage('info', 'Account deleted.');
		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
