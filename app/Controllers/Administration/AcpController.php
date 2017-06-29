<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers\Administration;

use \Sleeti\Controllers\Controller;

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

		$user = $this->container->auth->user();

		$this->container->log->notice('acp', $user->username . ' (' . $user->id . ') updated the database settings.');

		$this->container->flash->addMessage('success', 'Databse settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.database'));
	}

	public function getSiteSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/site.twig');
	}

	public function postSiteSettings($request, $response) {
		$config = $this->getConfigElements($request, ['title']);
		$config['upload'] = $this->getConfigElements($request, ['path']);

		if (substr($config['upload']['path'], -1) != '/') {
			$config['upload']['path'] .= '/';
		}

		if ($this->writeConfig(['site' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.site'));
		}

		$this->container->flash->addMessage('success', 'Site settings updated successfully!');

		$user = $this->container->auth->user();

		$this->container->log->notice('acp', $user->username . ' (' . $user->id . ') updated the site settings.');

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

		$user = $this->container->auth->user();

		$this->container->log->notice('acp', $user->username . ' (' . $user->id . ') updated the password settings.');

		$this->container->flash->addMessage('success', 'Password settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.password'));
	}

	public function getErrorSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/errors.twig');
	}

	public function postErrorSettings($request, $response) {
		$config = $this->getConfigElements($request, ['displayErrorDetails']);

		if ($this->writeConfig($config) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.errors'));
		}

		$user = $this->container->auth->user();

		$this->container->log->notice('acp', $user->username . ' (' . $user->id . ') updated the error settings.');

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

		$user = $this->container->auth->user();

		$this->container->log->notice('acp', $user->username . ' (' . $user->id . ') updated the reCAPTCHA settings.');

		$this->container->flash->addMessage('success', 'reCAPTCHA settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.recaptcha'));
	}

	public function getLogSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/log.twig', [
			'levels' => \Sleeti\Logging\Logger::LOG_LEVELS,
		]);
	}

	public function postLogSettings($request, $response) {
		$config = $this->getConfigElements($request, ['enabled', 'path', 'maxFiles', 'level']);

		if (substr($config['path'], -1) != '/') $config['path'] .= '/';

		if ($config['maxFiles'] < 0) $config['maxFiles'] = 0;

		$config['level'] = (int) $config['level'];

		if (!in_array($config['level'], \Sleeti\Logging\Logger::LOG_LEVELS)) {
			$config['level'] = \Sleeti\Logging\Logger::LOG_LEVELS['INFO'];
		}

		if ($this->writeConfig(['logging' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.log'));
		}

		$user = $this->container->auth->user();

		$this->container->log->notice('acp', $user->username . ' (' . $user->id . ') updated the log settings.');

		$this->container->flash->addMessage('success', 'Log settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.log'));
	}

	public function getCacheSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/cache.twig');
	}

	public function postCacheSettings($request, $response) {
		$config = $this->getConfigElements($request, ['enabled', 'path', 'auto_reload']);

		if (substr($config['path'], -1) != '/') $config['path'] .= '/';

		if ($this->writeConfig(['cache' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.cache'));
		}

		$user = $this->container->auth->user();

		$this->container->log->notice('acp', $user->username . ' (' . $user->id . ') updated the cache settings.');

		$this->container->flash->addMessage('success', 'Cache settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.cache'));
	}

	public function getMailSettings($request, $response) {
		return $this->container->view->render($response, 'admin/acp/mail.twig');
	}

	public function postMailSettings($request, $response) {
		$config = $this->getConfigElements($request, ['enabled', 'apikey', 'domain', 'address']);

		if ($this->writeConfig(['mail' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.acp.mail'));
		}

		$user = $this->container->auth->user();

		$this->container->log->notice('acp', $user->username . ' (' . $user->id . ') updated the mail settings.');

		$this->container->flash->addMessage('success', 'Mail settings updated successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp.mail'));
	}
}
