<?php

namespace Sleeti\Controllers;

use Sleeti\Models\User;

class CommunityController extends Controller
{
	const MAX_PER_PAGE = 10;

	public function community($request, $response) {
		$totalPages = ceil(User::count() / $this::MAX_PER_PAGE);
		$page       = $request->getParam('page') ?? 1;

		if ($page > $totalPages) {
			$page = $totalPages;
		} elseif ($page < 1) {
			$page = 1;
		}

		return $this->container->view->render($response, 'users.twig', [
			'page' => [
				'users'   => User::skip(($page - 1) * $this::MAX_PER_PAGE)->take($this::MAX_PER_PAGE)->get(),
				'current' => $page,
				'last'    => $totalPages,
			],
		]);
	}
}
