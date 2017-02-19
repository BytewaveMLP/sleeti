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

class CommunityController extends Controller
{
	public function community($request, $response) {
		$itemsPerPage = $this->container->auth->check() ? $this->container->auth->user()->settings->items_per_page : 10;
		$totalPages   = ceil(User::count() / $itemsPerPage);
		$page         = $request->getParam('page') ?? 1;

		if ($page > $totalPages) {
			$page = $totalPages;
		} elseif ($page < 1) {
			$page = 1;
		}

		return $this->container->view->render($response, 'users.twig', [
			'page' => [
				'users'   => User::skip(($page - 1) * $itemsPerPage)->take($itemsPerPage)->get(),
				'current' => $page,
				'last'    => $totalPages,
			],
		]);
	}
}
