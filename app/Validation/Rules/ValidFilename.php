<?php

namespace Sleeti\Validation\Rules;

use Sleeti\Models\User;
use Respect\Validation\Rules\AbstractRule;

class ValidFilename extends AbstractRule
{
	public function validate($input) {
		$filename = pathinfo($input, PATHINFO_FILENAME);
		$ext      = pathinfo($input, PATHINFO_EXTENSION);

		return !(strpbrk($filename, "\\/?%*:|\"<>") || strpbrk($ext, "\\/?%*:|\"<>"));
	}
}
