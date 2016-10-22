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

namespace Sleeti\Controllers\Auth;

use Sleeti\Controllers\Controller;
use Respect\Validation\Validator as v;

class TwoFactorAuthController extends Controller
{
	public function getEnable($request, $response) {
		return $this->container->view->render($response, 'user/2fa/enable.twig');
	}

	public function postEnable($request, $response) {
		$user   = $this->container->auth->user();
		$tfa    = $this->container->tfa;
		$enable = $request->getParam('enable') === '1';

		if ($enable) {
			$secret = $tfa->createSecret();
			$user->settings->tfa_secret = $secret;
			$user->settings->save();

			$this->container->flash->addMessage('info', 'Follow the instructions below to set up two-factor authentication for your account.');
			return $response->withRedirect($this->container->router->pathFor('user.profile.2fa.setup'));
		}

		$user->settings->tfa_enabled = false;
		$user->settings->tfa_secret  = null;
		$user->settings->save();

		$this->container->log->log('2FA', \Monolog\Logger::INFO, 'User disabled 2FA.', [
			'id'       => $user->id,
			'username' => $user->username,
		]);

		$this->container->flash->addMessage('success', 'Two-factor authentication successfully disabled.');
		return $response->withRedirect($this->container->router->pathFor('user.profile.edit'));
	}

	public function getSetup($request, $response) {
		$user   = $this->container->auth->user();
		$secret = $user->settings->tfa_secret;
		$tfa    = $this->container->tfa;

		if ($secret === null) {
			$this->container->flash->addMessage('danger', '<b>Hey!</b> You need to enable two-factor auth before you can set it up!');
			return $response->withRedirect($this->container->router->pathFor('user.profile.2fa'));
		}

		return $this->container->view->render($response, 'user/2fa/setup.twig', [
			'tfa' => [
				'qr_code' => $tfa->getQRCodeImageAsDataUri(($this->container['settings']['site']['title'] ?? "sleeti") . ':' . $user->username, $secret),
				'secret'  => $secret,
			],
		]);
	}

	public function postSetup($request, $response) {
		$tfa    = $this->container->tfa;
		$code   = $request->getParam('tfa_code');
		$user   = $this->container->auth->user();
		$secret = $user->settings->tfa_secret;

		$validation = $this->container->validator->validate($request, [
			'tfa_code' => v::twoFactorAuthCode($tfa, $secret),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like something isn\'t right...');
			return $response->withRedirect($this->container->router->pathFor('user.profile.2fa.setup'));
		}

		$user->settings->tfa_enabled = true;
		$user->settings->save();

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> You\'ve successfully enabled two-factor authentication!');

		$this->container->log->log('2FA', \Monolog\Logger::INFO, 'User enabled and set up 2FA.', [
			'id'       => $user->id,
			'username' => $user->username,
		]);

		return $response->withRedirect($this->container->router->pathFor('user.profile.edit'));
	}
}
