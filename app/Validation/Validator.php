<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Validation;

use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

/**
 * Respect validation wrapper
 */
class Validator
{
	protected $errors;

	/**
	 * Validates form data with the given Respect rules
	 * @param  array  $rules   The rules to validate against
	 * @return Validator       A new Sleeti validator with the given errors
	 */
	public function validate($request, array $rules) {
		foreach ($rules as $field => $rule) {
			try {
				$rule->setName(ucfirst($field))->assert($request->getParam($field));
			} catch (NestedValidationException $e) {
				$this->errors[$field] = $e->getMessages();
			}
		}

		$_SESSION['errors'] = $this->errors;

		return $this;
	}

	public function failed() {
		return !empty($this->errors);
	}
}
