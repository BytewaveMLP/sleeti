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

namespace Sleeti\Controllers\Administration;

use Sleeti\Controllers\Controller;

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
		return $response->withRedirect($this->container->router->pathFor('home'));
	}
}
