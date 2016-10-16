<?php

/**
 * This file is part of sleeti.
 * Copyright (C) 2016  Eliot Partridge
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
