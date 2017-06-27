<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers\Administration;

use \Sleeti\Controllers\Controller;
use \Sleeti\Models\User;

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
		} elseif ($user->id === $this->container->auth->user()->id) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> You can\'t change your own group!');
			return $response->withStatus(403)->withRedirect($this->container->router->pathFor('home'));
		}

		$group    = $request->getParam('group');
		$oldGroup = $user->permissions->flags;

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

		$authedUser = $this->container->auth->user();

		$this->container->log->notice('acp', $authedUser->username . ' (' . $authedUser->id . ') changed ' . $user->username . ' (' . $user->id . ')\'s usergroup from ' . $oldGroup . ' to ' . $user->permissions->flags . ' .');

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> ' . $user->username . '\'s usergroup was changed successfully.');
		return $response->withRedirect($this->container->router->pathFor('user.profile', [
			'id' => $user->id,
		]));
	}
}
