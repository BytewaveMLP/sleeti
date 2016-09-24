<?php

namespace Sleeti\Controllers\Administration;

use Sleeti\Controllers\Controller;
use Sleeti\Models\User;

class AdminController extends Controller
{
	public function getAddPermissionsPage($request, $response, $args) {
		$id = $args['uid'];
		$user = User::where('id', $id)->first();

		if ($user === null) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		} elseif ($user === $this->container->auth->user()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> You can\'t change your own group!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		return $this->container->view->render($response, 'admin/user/giveperms.twig', [
			'user' => $user,
		]);
	}

	public function postAddPermissionsPage($request, $response, $args) {
		$id = $args['uid'];
		$user = User::where('id', $id)->first();

		if ($user === null) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		} elseif ($user === $this->container->auth->user()) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> You can\'t change your own group!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$group = $request->getParam('group');

		if ($group === "admin") {
			$user->removePermission('M');
			$user->addPermission('A');
		} elseif ($group === "mod") {
			$user->removePermission('A');
			$user->addPermission('M');
		} elseif ($group === "none") {
			$user->removePermission('M');
			$user->removePermission('A');
		}

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> ' . $user->username . '\'s usergroup was changed successfully.');
		return $response->withRedirect($this->container->router->pathFor('user.profile', [
			'id' => $user->id,
		]));
	}
}
