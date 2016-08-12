<?php

namespace Eeti\Controllers;

class AdminController extends Controller
{
	private function writeConfig(array $config) {
		$newConfig = array_merge($this->container['config'], $config);
		return file_put_contents(__DIR__ . '/../../config/config.json', json_encode($newConfig, JSON_PRETTY_PRINT));
	}

	private function getConfigElements($request, array $toGet) {
		foreach ($toGet as $key) {
			$elements[$key] = $request->getParam($key);
		}
		return $elements;
	}

	public function getAcpHome($request, $response) {
		return $this->container->view->render($response, 'admin/acp.twig');
	}

	public function getDatabaseSettings($request, $response) {
		return $this->container->view->render($response, 'admin/database.twig');
	}

	public function postDatabaseSettings($request, $response) {
		$config = $this->getConfigElements($request, ['driver', 'host', 'database', 'username', 'password', 'charset', 'collation']);

		if ($this->writeConfig(['db' => $config]) === false) {
			$this->container->flash->addMessage('danger', '<b>Uh oh!</b> Looks like <code>/config/config.json</code> failed to write. :(');
			return $response->withRedirect($this->container->router->pathFor('admin.database'));
		}

		$this->container->flash->addMessage('success', 'Config written successfully!');
		return $response->withRedirect($this->container->router->pathFor('admin.acp'));
	}

	public function getSiteSettings($request, $response) {
		return $this->container->view->render($response, 'admin/site.twig');
	}

	public function postSiteSettings($request, $response) {
		return 'ok';
	}

	public function getPasswordSettings($request, $response) {
		return $this->container->view->render($response, 'admin/password.twig');
	}

	public function postPasswordSettings($request, $response) {
		return 'posted';
	}
}
