<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Mail;

class Message
{
	protected $builder;

	public function __construct($builder) {
		$this->builder = $builder;
	}

	public function to($recipient) {
		$this->builder->addToRecipient($recipient);
	}

	public function subject($subject) {
		$this->builder->setSubject($subject);
	}

	public function body($body) {
		$this->builder->setHtmlBody($body);
	}

	public function textBody($body) {
		$this->builder->setTextBody($body);
	}
}
