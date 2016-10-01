<?php

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
