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

namespace Sleeti\Twig\Extensions;

class BootstrapActiveExtension extends \Twig_Extension {
	protected $request;

	public function __construct($request) {
		$this->request = $request;
	}

	public function getFunctions() {
		return [
			new \Twig_SimpleFunction('active_route', [$this, 'isActiveRoute']),
			new \Twig_SimpleFunction('active_route_one_of', [$this, 'isActiveRouteOneOf']),
			new \Twig_SimpleFunction('active_group', [$this, 'isActiveRouteInGruop']),
			new \Twig_SimpleFunction('active_group_one_of', [$this, 'isActiveRouteInGroups']),
		];
	}

	public function isActiveRoute(string $name) {
		$route = $this->request->getAttribute('route');

		if (!$route) return;

		if ($route->getName() == $name) {
			return 'active';
		}
	}

	public function isActiveRouteOneOf(array $names) {
		foreach ($names as $name) {
			if ($this->isActiveRoute($name)) {
				return 'active';
			}
		}
	}

	public function isActiveRouteInGruop(string $group) {
		$route = $this->request->getAttribute('route');

		if (!$route) return;

		$routeName = $route->getName();

		$routeParts = explode('.', $routeName);
		$groupParts = explode('.', $group);

		foreach ($groupParts as $i => $part) {
			if ($routeParts[$i] !== $part) return;
		}

		return 'active';
	}

	public function isActiveRouteInGroups(array $groups) {
		foreach ($groups as $group) {
			if ($this->isActiveRouteInGruop($group)) {
				return 'active';
			}
		}
	}
}
