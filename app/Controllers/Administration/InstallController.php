<?php

namespace Eeti\Controllers\Administration;

use Eeti\Controllers\Controller;

class InstallController extends Controller
{
	public function getInstall($request, $response) {
		return $this->container->view->render($response, 'install.twig');
	}

	public function postInstall($request, $response) {
		// Yes, this is ugly.
		// No, I don't really care. :)
		$settings = [
			'site' => [
				'title' => $request->getParam('title'),
			],
			'db' => [
				'driver'    => $request->getParam('dbdriver'),
				'host'      => $request->getParam('dbhost'),
				'database'  => $request->getParam('dbname'),
				'username'  => $request->getParam('dbuser'),
				'password'  => $request->getParam('dbpass'),
				'charset'   => $request->getParam('dbcharset'),
				'collation' => $request->getParam('dbcollation'),
			],
			'password' => [
				'cost' => (int) ($request->getParam('hashcost')),
			],
			'upload' => [
				'path' => $request->getParam('uploadpath'), -1) . (substr($request->getParam('uploadpath'), -1) !== '/' ? '/' : ''),
			],
		];

		if (file_put_contents(__DIR__ . '/../../config/config.json', json_encode($settings, JSON_PRETTY_PRINT)) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('install'));
		}

		touch(__DIR__ . '/../../config/lock');

		$this->container->flash->addMessage('success', '<b>Success!</b> Your new instance of ' . $request->getParam('title') . ' has been configured! To edit the config, see <code>/config/config.json</code> and the ACP.');
		$this->container->flash->addMessage('info', 'The first registered account will have administrator permissions. Register an account now.');
		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
