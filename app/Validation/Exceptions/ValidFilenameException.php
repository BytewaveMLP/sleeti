<?php

namespace Sleeti\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class UsernameAvailableException extends ValidationException
{
	public static $defaultTemplates = [
		self::MODE_DEFAULT => [
			self::STANDARD => 'Filenames must not contain any of the following: \/?%*:|"<>',
		],
	];
}
