<?php

namespace Eeti\Controllers\Administration;

use Eeti\Controllers\Controller;
use Eeti\Models\User;

class AdminController extends Controller
{
	public function getAddPermissionsPage($request, $response, $args) {
		$id = $args['uid'];

		if (User::where('id', $id)->count() === 0) {
			// throw new \Slim\Exception\NotFoundException($request, $response);
		}

		return $this->container->view->render($response, 'admin/user/giveperms.twig', [
			'user' => User::where('id', $id)->first(),
		]);
	}

	public function postAddPermissionsPage($request, $response, $args) {
		$id = $args['uid'];

		if (User::where('id', $id)->count() === 0) {
			throw new \Slim\Exception\NotFoundException($request, $response);
		}

		$user = User::where('id', $id)->first();

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
	}
}
