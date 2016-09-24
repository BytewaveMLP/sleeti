<?php

namespace Sleeti\Controllers\Moderation;

use Sleeti\Controllers\Controller;
use \Sleeti\Models\File;

class McpController extends Controller
{
	public function getMcpHome($request, $response) {
		return $this->container->view->render($response, 'mod/mcp/home.twig');
	}

	public function getFiles($request, $response) {
		return $this->container->view->render($response, 'mod/mcp/files.twig', [
			'files' => File::all(),
		]);
	}
}
