<?php

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
				'files'   => File::skip(($page - 1) * $this::MAX_PER_PAGE)->take($this::MAX_PER_PAGE)->get(),
				'current' => $page,
				'last'    => $totalPages,
			],
		]);
	}
}
