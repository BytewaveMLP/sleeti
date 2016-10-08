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
