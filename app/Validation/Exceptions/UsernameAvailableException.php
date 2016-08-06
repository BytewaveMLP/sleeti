<?php

namespace Eeti\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class UsernameAvailableException extends ValidationException
{
	public static $defaultTemplates = [
		self::MODE_DEFAULT => [
			self::STANDARD => 'Username is already in use',
		],
	];
}
