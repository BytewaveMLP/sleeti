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
