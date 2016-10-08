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

$container['HomeController'] = function ($container) {
	return new \Sleeti\Controllers\HomeController($container);
};

$container['AuthController'] = function ($container) {
	return new \Sleeti\Controllers\Auth\AuthController($container);
};

$container['PasswordController'] = function ($container) {
	return new \Sleeti\Controllers\Auth\PasswordController($container);
};

$container['AcpController'] = function ($container) {
	return new \Sleeti\Controllers\Administration\AcpController($container);
};

$container['AdminController'] = function ($container) {
	return new \Sleeti\Controllers\Administration\AdminController($container);
};

$container['McpController'] = function ($container) {
	return new \Sleeti\Controllers\Moderation\McpController($container);
};

$container['FileController'] = function ($container) {
	return new \Sleeti\Controllers\FileController($container);
};

$container['ProfileController'] = function ($container) {
	return new \Sleeti\Controllers\ProfileController($container);
};

$container['CommunityController'] = function ($container) {
	return new \Sleeti\Controllers\CommunityController($container);
};

$container['InstallController'] = function ($container) {
	return new \Sleeti\Controllers\Administration\InstallController($container);
};
