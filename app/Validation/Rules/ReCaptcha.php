<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Validation\Rules;

use Sleeti\Models\User;
use Respect\Validation\Rules\AbstractRule;

class ReCaptcha extends AbstractRule
{
	protected $secretKey;

	public function __construct($secretKey) {
		$this->secretKey = $secretKey;
	}

	public function validate($input) {
		$response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $this->secretKey . '&response=' . $input);
		$data     = json_decode($response);

		return $data->success;
	}
}
