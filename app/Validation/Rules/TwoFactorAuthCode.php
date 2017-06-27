<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Validation\Rules;

use Sleeti\Models\UserTfaRecoveryToken;
use Respect\Validation\Rules\AbstractRule;

class TwoFactorAuthCode extends AbstractRule
{
	protected $tfa;

	protected $user;

	public function __construct($tfa, $user) {
		$this->tfa  = $tfa;
		$this->user = $user;
	}

	public function validate($input) {
		$recoveryToken = UserTfaRecoveryToken::where('user_id', $this->user->id)->where('token', hash('sha384', $input))->first();

		if ($recoveryToken) {
			$recoveryToken->delete();
			return true;
		}

		return $this->tfa->verifyCode($this->user->settings->tfa_secret, $input);
	}
}
