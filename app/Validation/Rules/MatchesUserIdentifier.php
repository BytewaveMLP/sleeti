<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Validation\Rules;

use Sleeti\Models\User;
use Respect\Validation\Rules\AbstractRule;

class MatchesUserIdentifier extends AbstractRule
{
	protected $user;

	public function __construct(User $user) {
		$this->user = $user;
	}

	public function validate($input) {
		return $input === $this->user->username || $input === $this->user->identifier;
	}
}
