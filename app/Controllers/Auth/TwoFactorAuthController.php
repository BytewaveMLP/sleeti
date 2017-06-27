<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Controllers\Auth;

use \Sleeti\Controllers\Controller;
use \Sleeti\Models\UserTfaRecoveryToken;
use \Respect\Validation\Validator as v;

class TwoFactorAuthController extends Controller
{
	const NUM_TFA_RECOVERY_CODES   = 10;
	const TFA_RECOVERY_CODE_LENGTH = 10;

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

		foreach ($user->tfaRecoveryTokens as $token) {
			$token->delete();
		}

		$this->container->log->info('2FA', $user->username . ' (' . $user->id . ') disabled 2FA.');

		$this->container->flash->addMessage('success', 'Two-factor authentication successfully disabled.');
		return $response->withRedirect($this->container->router->pathFor('user.profile.2fa'));
	}

	public function getSetup($request, $response) {
		$user   = $this->container->auth->user();
		$secret = $user->settings->tfa_secret;
		$tfa    = $this->container->tfa;

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

		$validation = $this->container->validator->validate($request, [
			'tfa_code' => v::twoFactorAuthCode($tfa, $user),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('danger', '<b>Whoops!</b> Looks like something isn\'t right...');
			return $response->withRedirect($this->container->router->pathFor('user.profile.2fa.setup'));
		}

		$user->settings->tfa_enabled = true;
		$user->settings->save();

		$tokens = [];

		for ($i = 0; $i < $this::NUM_TFA_RECOVERY_CODES; $i++) {
			$tokens[$i] = $this->container->randomlib->generateString($this::TFA_RECOVERY_CODE_LENGTH);
			$hash       = hash('sha384', $tokens[$i]);
			UserTfaRecoveryToken::create([
				'user_id' => $user->id,
				'token'   => $hash,
			]);
		}

		$this->container->flash->addMessage('success', '<b>Woohoo!</b> You\'ve successfully enabled two-factor authentication!');

		$this->container->log->info('2FA', $user->username . ' (' . $user->id . ') enabled and set up 2FA.');

		return $this->container->view->render($response, 'user/2fa/recovery-tokens.twig', [
			'tokens' => $tokens,
		]);
	}
}
