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

	/**
	 * @param string|null $instanceName
	 */
	public function __construct($instanceName = null)
	{
		$this->engine = \Parsedown::instance($instanceName);
		$this->engine->setMarkupEscaped(true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function transform($content)
	{
		return $this->engine->parse($content);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'erusev/parsedown (safe)';
	}
}
