<?php

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

		$totalPages = ceil($user->files->count() / $this::MAX_PER_PAGE);

		if ($page > $totalPages) {
			$page = $totalPages;
		} elseif ($page < 1) {
			$page = 1;
		}

		return $this->container->view->render($response, 'user/profile.twig', [
			'user' => $user,
			'page' => [
				'files'   => $user->files()->skip(($page - 1) * $this::MAX_PER_PAGE)->take($this::MAX_PER_PAGE)->get(),
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

		$bio = preg_replace('~\r\n?~', "\n", $bio);

		$validation = $this->container->validator->validate($request, [
			'website' => v::optional(v::url())->length(null, 255),
			'bio'     => v::length(null, 500),
			'name'    => v::length(null, 50),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Oh no!</b> Something went wrong.');
			return $response->withRedirect($this->container->router->pathFor('user.profile.edit'));
		}

		if ($privacy == 'public') {
			$user->default_privacy_state = 0;
		} elseif ($privacy == 'unlisted') {
			$user->default_privacy_state = 1;
		} elseif ($privacy == 'private') {
			$user->default_privacy_state = 2;
		}

		$user->website = $website;
		$user->bio     = strip_tags($bio); // no XSS 4 u
		$user->name    = $name;
		$user->save();

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> Your profile was updated successfully.');
		return $response->withRedirect($this->container->router->pathFor('user.profile', ['id' => $user->id]));
	}
}
