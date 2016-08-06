<?php

namespace Eeti\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class PasswordConfirmationException extends ValidationException
{
	public static $defaultTemplates = [
		self::MODE_DEFAULT => [
			self::STANDARD => 'Passwords do not match',
		],
	];
}
