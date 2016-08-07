<?php

namespace Eeti\Controllers;

use Eeti\Models\User;

class ProfileController extends Controller
{
	public function viewProfile($request, $response, $args) {
		$id = $args['id'];

		if (User::where('id', $id)->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		return $this->container->view->render($response, 'user/profile.twig', [
			'user' => User::where('id', $id)->first(),
		]);
	}
}
