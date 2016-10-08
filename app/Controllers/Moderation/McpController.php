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

use Sleeti\Controllers\Controller;
use \Sleeti\Models\File;

class McpController extends Controller
{
	const MAX_PER_PAGE = 10;

	public function getMcpHome($request, $response) {
		return $this->container->view->render($response, 'mod/mcp/home.twig');
	}

	public function getFiles($request, $response) {
		$totalPages = ceil(File::count() / $this::MAX_PER_PAGE);
		$page       = $request->getParam('page') ?? 1;

		if ($page > $totalPages) {
			$page = $totalPages;
		} elseif ($page < 1) {
			$page = 1;
		}

		return $this->container->view->render($response, 'mod/mcp/files.twig', [
			'page' => [
				'files'   => File::orderBy('id', 'DESC')->skip(($page - 1) * $this::MAX_PER_PAGE)->take($this::MAX_PER_PAGE)->get(),
				'current' => $page,
				'last'    => $totalPages,
			],
		]);
	}
}
