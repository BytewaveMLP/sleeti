<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Mail;

class Mailer
{
	protected $container;

	protected $mailer;

	public function __construct($container, $mailer) {
		$this->container = $container;
		$this->mailer = $mailer;
	}

	public function send($template, $data, $callback) {
		$builder = $this->mailer->MessageBuilder();
		$builder->setFromAddress($this->container['settings']['mail']['address']);

		$message = new Message($builder);

		$body = $this->container->view->fetch($template, $data);
		$preMailer = new \Crossjoin\PreMailer\HtmlString($body);
		$message->body($preMailer->getHtml());
		$message->textBody($preMailer->getText());

		call_user_func($callback, $message);

		$domain = $this->container['settings']['mail']['domain'];
		$this->mailer->post("{$domain}/messages", $builder->getMessage());
	}
}
