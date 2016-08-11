<?php

$container['HomeController'] = function ($container) {
	return new \Eeti\Controllers\HomeController($container);
};

$container['AuthController'] = function ($container) {
	return new \Eeti\Controllers\Auth\AuthController($container);
};

$container['PasswordController'] = function ($container) {
	return new \Eeti\Controllers\Auth\PasswordController($container);
};

$container['AdminController'] = function ($container) {
	return new \Eeti\Controllers\AdminController($container);
};

$container['FileController'] = function ($container) {
	return new \Eeti\Controllers\FileController($container);
};

$container['ProfileController'] = function ($container) {
	return new \Eeti\Controllers\ProfileController($container);
};

$container['CommunityController'] = function ($container) {
	return new \Eeti\Controllers\CommunityController($container);
};

$container['InstallController'] = function ($container) {
	return new \Eeti\Controllers\InstallController($container);
};
