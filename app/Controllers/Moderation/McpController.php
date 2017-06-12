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
