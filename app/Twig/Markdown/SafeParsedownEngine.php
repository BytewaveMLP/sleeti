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

	public function __construct()
	{
		$this->engine = new \Aidantwoods\SecureParsedown\SecureParsedown;
		$this->engine->setSafeMode(true);
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
