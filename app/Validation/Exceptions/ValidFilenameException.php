<?php

namespace Sleeti\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class ValidFilenameException extends ValidationException
{
	public static $defaultTemplates = [
		self::MODE_DEFAULT => [
			self::STANDARD => 'Filenames must not contain any of the following: \/?%*:|"<>',
		],
	];
}
