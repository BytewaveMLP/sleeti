<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers\Moderation;

use \Sleeti\Controllers\Controller;
use \Sleeti\Models\File;
use \Sleeti\Models\User;
use \Sleeti\Twig\Extensions\FileHelperExtension;

class McpController extends Controller
{
	public function getMcpHome($request, $response) {
		return $this->container->view->render($response, 'mod/mcp/home.twig');
	}

	public function getFiles($request, $response) {
		$itemsPerPage = $this->container->auth->check() ? $this->container->auth->user()->settings->items_per_page : 10;
		$totalPages   = ceil(File::count() / $itemsPerPage);
		$page         = $request->getParam('page') ?? 1;

		if ($page > $totalPages) {
			$page = $totalPages;
		} elseif ($page < 1) {
			$page = 1;
		}

		return $this->container->view->render($response, 'mod/mcp/files.twig', [
			'page' => [
				'files'   => File::orderBy('id', 'DESC')->skip(($page - 1) * $itemsPerPage)->take($itemsPerPage)->get(),
				'current' => $page,
				'last'    => $totalPages,
			],
		]);
	}

	public function getSiteStats($request, $response) {
		$files = File::all();
		$users = User::with('files')->get();
		$container = $this->container;
		return $container->view->render($response, 'mod/mcp/stats.twig', [
			'files' => $files,
			'usersFiles' => $users->sortBy(function ($user) {
				return $user->files->count();
			}, SORT_NUMERIC, true),
			'usersSizes' => $users->sortBy(function ($user) use ($container) {
				return FileHelperExtension::dirsize($container['settings']['site']['upload']['path'] . $user->id);
			}, SORT_NUMERIC, true),
		]);
	}
}
