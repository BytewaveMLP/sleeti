<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers;

use \Sleeti\Models\User;

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
