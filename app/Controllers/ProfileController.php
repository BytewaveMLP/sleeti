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

namespace Sleeti\Controllers;

use Sleeti\Models\User;
use Respect\Validation\Validator as v;

class ProfileController extends Controller
{
	const MAX_PER_PAGE = 10;

	public function viewProfile($request, $response, $args) {
		$id    = $args['id'];
		$page  = $request->getParam('page') ?? 1;
		$users = User::where('id', $id);

		// If no users have the given ID, return 404
		if ($users->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$user = $users->first();

		$files = $user->files()->orderBy('id', 'DESC');

		if (!$this->container->auth->check() || ($this->container->auth->user()->id != $user->id && !$this->container->auth->user()->isModerator())) {
			$files = $files->where('privacy_state', 0);
		}

		$itemsPerPage = $this->container->auth->check() ? $this->container->auth->user()->settings->items_per_page : 10;
		$totalPages   = ceil($files->get()->count() / $itemsPerPage);

		if ($page > $totalPages) {
			$page = $totalPages;
		} elseif ($page < 1) {
			$page = 1;
		}

		return $this->container->view->render($response, 'user/profile.twig', [
			'user' => $user,
			'page' => [
				'files'   => $files->skip(($page - 1) * $itemsPerPage)->take($itemsPerPage)->get(),
				'current' => $page,
				'last'    => $totalPages,
			],
		]);
	}

	public function getEditProfile($request, $response) {
		return $this->container->view->render($response, 'user/update.twig');
	}

	public function postEditoProfile($request, $response) {
		$user = $this->container->auth->user();

		$website = $request->getParam('website');
		$bio     = $request->getParam('bio');
		$name    = $request->getParam('name');

		$privacy = $request->getParam('privacy');

		$itemsPerPage = $request->getParam('items_per_page');

		$bio = preg_replace('~\r\n?~', "\n", $bio);

		$validation = $this->container->validator->validate($request, [
			'website'        => v::optional(v::url())->length(null, 255),
			'bio'            => v::length(null, 500),
			'name'           => v::length(null, 50),
			'items_per_page' => v::intVal()->between(5, 50),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> Something went wrong.');
			return $response->withRedirect($this->container->router->pathFor('user.profile.edit'));
		}

		if ($privacy == 'public') {
			$user->settings->default_privacy_state = 0;
		} elseif ($privacy == 'unlisted') {
			$user->settings->default_privacy_state = 1;
		} elseif ($privacy == 'private') {
			$user->settings->default_privacy_state = 2;
		}

		$user->settings->items_per_page = $itemsPerPage;

		$user->settings->save();

		$user->website = $website;
		$user->bio     = strip_tags($bio); // no XSS 4 u
		$user->name    = $name;

		$user->save();

		$this->container->log->log('profile', \Monolog\Logger::INFO, 'User profile updated.', [
			$user->id,
			$user->username,
		]);

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your profile was updated successfully.');
		return $response->withRedirect($this->container->router->pathFor('user.profile', ['id' => $user->id]));
	}
}
