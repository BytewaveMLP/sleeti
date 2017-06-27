<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

$container['HomeController'] = function ($container) {
	return new \Sleeti\Controllers\HomeController($container);
};

$container['AuthController'] = function ($container) {
	return new \Sleeti\Controllers\Auth\AuthController($container);
};

$container['TwoFactorAuthController'] = function ($container) {
	return new \Sleeti\Controllers\Auth\TwoFactorAuthController($container);
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
