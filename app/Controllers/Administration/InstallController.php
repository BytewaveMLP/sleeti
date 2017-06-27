<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers\Administration;

use \Sleeti\Controllers\Controller;

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
				'upload' => [
					'path' => $request->getParam('uploadpath') . (substr($request->getParam('uploadpath'), -1) !== '/' ? '/' : ''),
				],
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
			'recaptcha' => [
				'enabled'   => $request->getParam('recaptcha-enabled') == '1',
				'sitekey'   => $request->getParam('recaptcha-sitekey'),
				'secretkey' => $request->getParam('recaptcha-secretkey'),
			],
		];

		if (file_put_contents(__DIR__ . '/../../../config/config.json', json_encode($settings, JSON_PRETTY_PRINT)) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('install'));
		}

		touch(__DIR__ . '/../../../config/lock');

		$this->container->flash->addMessage('success', '<b>Success!</b> Your new instance of ' . $request->getParam('title') . ' has been configured! To edit the config, see <code>/config/config.json</code> and the ACP.');
		$this->container->flash->addMessage('info', 'The first registered account will have administrator permissions. Register an account now.');

		$this->container->log->debug('install', 'Sleeti instance installed successfully.');

		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
