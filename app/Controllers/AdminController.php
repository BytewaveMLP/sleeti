<?php

namespace Eeti\Controllers;

class AdminController extends Controller
{
	public function getAcp($request, $response) {
		return $this->container->view->render($response, 'admin/acp.twig');
	}

	public function postAcp($request, $response) {
		return 'posted';
	}
}
