<?php

namespace Sleeti\Controllers\Auth;

use Sleeti\Controllers\Controller;
use Sleeti\Models\User;
use Sleeti\Models\UserPermission;
use Respect\Validation\Validator as v;

class AuthController extends Controller
{
	public function getSignOut($request, $response) {
		$this->container->auth->signout();

		$this->container->flash->addMessage('info', 'You have been logged out.');
		return $response->withRedirect($this->container->router->pathFor('home'));
	}

	public function getSignIn($request, $response) {
		return $this->container->view->render($response, 'auth/signin.twig');
	}

	public function postSignIn($request, $response) {
		$auth = $this->container->auth->attempt(
			$request->getParam('identifier'),
			$request->getParam('password')
		);

		if (!$auth) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> We couldn\'t find an account with those details.');
			return $response->withRedirect($this->container->router->pathFor('auth.signin'));
		}

		$this->container->flash->addMessage('success', '<b>Success!</b> Welcome back!');
		return $response->withRedirect($this->container->router->pathFor('home'));
	}

	public function getSignUp($request, $response) {
		return $this->container->view->render($response, 'auth/signup.twig');
	}

	public function postSignUp($request, $response) {
		$validation = $this->container->validator->validate($request, [
			'email'            => v::notEmpty()->noWhitespace()->email()->emailAvailable(),
			'username'         => v::notEmpty()->alnum('-_')->noWhitespace()->usernameAvailable(),
			'password'         => v::notEmpty(),
			'password_confirm' => v::passwordConfirmation($request->getParam('password')),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like something went wrong.');
			return $response->withRedirect($this->container->router->pathFor('auth.signup'));
		}

		$user = User::create([
			'email'    => $request->getParam('email'),
			'username' => $request->getParam('username'),
			'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT, $this->container['settings']['password'] ?? ['cost' => 10]),
		]);

		$userPerms = UserPermission::create([
			'user_id' => $user->id,
			'flags'   => '',
		]);

		$userPerms->user()->associate($user);

		$this->container->flash->addMessage('success', '<b>Success!</b> Welcome to ' . $this->container->settings['site']['title'] ?? 'sleeti' . '!');

		if ($user->id === 1) { // if this is the only user, give them admin
			$user->addPermission('A');
			$this->container->flash->addMessage('info', 'New administrative account created!');
		}

		$this->container->auth->attempt(
			$request->getParam('email'),
			$request->getParam('password')
		);

		return $response->withRedirect($this->container->router->pathFor('home'));
	}

	public function getDeleteAccount($request, $response, $args) {
		$args['id'] = $args['id'] ?? $this->container->auth->user()->id;

		if (User::where('id', $args['id'])->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		if ($this->container->auth->user()->id != $args['id'] && !$this->container->auth->user()->isAdmin()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> What do you think you\'re doing?! You can\'t delete someone else\'s account!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		return $this->container->view->render($response, 'user/delete.twig', [
			'id' => $args['id'] ?? $this->container->auth->user()->id,
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
			var_dump($this->container['settings']['site']['upload']['path'] . $file->getPath());
			unlink($this->container['settings']['site']['upload']['path'] . $file->getPath());
			$file->delete();
		}

		$path = $this->container['settings']['site']['upload']['path'] . $user->id;

		if (is_dir($path)) {
			rmdir($path);
		}

		$user->delete();

		$this->container->auth->signout();

		$this->container->flash->addMessage('info', 'Account deleted.');
		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
