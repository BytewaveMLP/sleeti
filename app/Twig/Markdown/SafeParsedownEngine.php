<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Sleeti\Twig\Markdown;

use \Aptoma\Twig\Extension\MarkdownEngineInterface;

class SafeParsedownEngine implements MarkdownEngineInterface {
	/**
	 * @var Parsedown
	 */
	protected $engine;

	protected $htmlPurifier;

	/**
	 * @param string|null $instanceName
	 */
	public function __construct($instanceName = null)
	{
		$this->engine = \Parsedown::instance($instanceName);
		$this->engine->setMarkupEscaped(true);

		$this->htmlPurifier = new \HTMLPurifier(\HTMLPurifier_Config::createDefault());
	}

	/**
	 * {@inheritdoc}
	 */
	public function transform($content)
	{
		$parsed = $this->engine->parse($content);

		return $this->htmlPurifier->purify($parsed);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'erusev/parsedown (safe/purified)';
	}
}
