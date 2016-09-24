<?php

namespace Sleeti\Controllers;

use Sleeti\Models\User;

class CommunityController extends Controller
{
	public function community($request, $response) {
		return $this->container->view->render($response, 'users.twig', [
			'users' => User::all(),
		]);
	}
}
