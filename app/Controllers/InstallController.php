<?php

namespace Eeti\Controllers;

class InstallController extends Controller
{
	public function getInstall($request, $response) {
		return $this->container->view->render($response, 'install.twig');
	}

	public function postInstall($request, $response) {
		return 'lol';
	}
}
