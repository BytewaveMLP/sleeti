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

class AcpController extends Controller
{
	/**
	 * Merge current config with given config, and write to application config
	 * @param  array   $config The config to merge and write with
	 * @return boolean Did the file writing succeed?
	 */
	private function writeConfig(array $config) {
		$newConfig = array_merge($this->container['config'], $config);
		return file_put_contents(__DIR__ . '/../../../config/config.json', json_encode($newConfig, JSON_PRETTY_PRINT));
	}

	/**
	 * Construct a new config array from the app Request and the given keys
	 * @param  array $toGet   The keys to extract from the Request object
	 * @return array The new config array
	 */
	private function getConfigElements($request, array $toGet) {
		foreach ($toGet as $key) {
			$elements[$key] = $request->getParam($key);
		}
		return $elements;
	}

	public function getAcpHome($request, $response) {
		return $this->container->view->render($response, 'admin/acp/home.twig');
	}

	public function getDatabaseSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/database.twig');
	}

	public function postDatabaseSettings($request, $response) {
		$config = $this->getConfigElements($request, ['driver', 'host', 'database', 'username', 'password', 'charset', 'collation']);

		if ($this->writeConfig(['db' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.database'));
		}

		$this->container->flash->addMessage('success', 'Databse settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.database'));
	}

	public function getSiteSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/site.twig');
	}

	public function postSiteSettings($request, $response) {
		$config           = $this->getConfigElements($request, ['title']);
		$config['upload'] = $this->getConfigElements($request, ['path']);

		if (substr($config['upload']['path'], -1) != '/') {
			$config['upload']['path'] .= '/';
		}

		if ($this->writeConfig(['site' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.site'));
		}

		$this->container->flash->addMessage('success', 'Site settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.site'));
	}

	public function getPasswordSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/password.twig');
	}

	public function postPasswordSettings($request, $response) {
		$config = $this->getConfigElements($request, ['cost']);

		if ($this->writeConfig(['password' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.password'));
		}

		$this->container->flash->addMessage('success', 'Password settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.password'));
	}

	public function getErrorSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/errors.twig');
	}

	public function postErrorSettings($request, $response) {
		$config = $this->getConfigElements($request, ['displayErrorDetails']);

		$config['displayErrorDetails'] = $config['displayErrorDetails'] == '1';

		if ($this->writeConfig($config) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.errors'));
		}

		$this->container->flash->addMessage('success', 'Error settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.errors'));
	}

	public function getReCaptchaSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/recaptcha.twig');
	}

	public function postReCaptchaSettings($request, $response) {
		$config = $this->getConfigElements($request, ['enabled', 'sitekey', 'secretkey']);

		if ($this->writeConfig(['recaptcha' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.recaptcha'));
		}

		$this->container->flash->addMessage('success', 'reCAPTCHA settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.recaptcha'));
	}
}
