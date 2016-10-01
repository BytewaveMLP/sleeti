<?php

namespace Sleeti\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class MatchesUserIdentifierException extends ValidationException
{
	public static $defaultTemplates = [
		self::MODE_DEFAULT => [
			self::STANDARD => 'Incorrect username entered',
		],
	];
}
