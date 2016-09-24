<?php

namespace Sleeti\Validation\Rules;

use Sleeti\Models\User;
use Respect\Validation\Rules\AbstractRule;

class PasswordConfirmation extends AbstractRule
{
	protected $password;

	public function __construct($password) {
		$this->password = $password;
	}

	public function validate($input) {
		return $input === $this->password;
	}
}
