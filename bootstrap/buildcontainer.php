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

use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;

// Save decoded config.json for modification through ACP (nasty hack, I know :()
$container['config'] = function($container) use ($settings) {
	return $settings['settings'];
};

$container['db'] = function($container) use ($capsule) {
	return $capsule;
};

$container['auth'] = function ($container) {
	return new \Sleeti\Auth\Auth($container);
};

$container['flash'] = function ($container) {
	return new \Slim\Flash\Messages;
};

$container['view'] = function ($container) {
	$view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
		'cache' => false,
	]);

	$view->addExtension(new \Slim\Views\TwigExtension(
		$container->router,
		$container->request->getUri()
	));

	$markdownEngine = new MarkdownEngine\ParsedownEngine;

	$view->addExtension(new MarkdownExtension($markdownEngine));

	$view->addExtension(new \Sleeti\Twig\Extensions\ReCaptchaExtension($container['settings']['recaptcha']['sitekey']));

	$view->getEnvironment()->addGlobal('auth', [
		'check' => $container->auth->check(),
		'user'  => $container->auth->user(),
	]);

	$view->getEnvironment()->addGlobal('flash', $container->flash);

	$view->getEnvironment()->addGlobal('settings', $container->settings);

	return $view;
};

$container['validator'] = function ($container) {
	return new \Sleeti\Validation\Validator;
};

$container['csrf'] = function ($container) {
	return new \Slim\Csrf\Guard;
};

$container['tfa'] = function ($container) {
	return new \RobThree\Auth\TwoFactorAuth($container['settings']['site']['title'] ?? "sleeti");
};

$container['randomlib'] = function ($container) {
	$factory  = new \RandomLib\Factory;
	$strength = new \SecurityLib\Strength(\SecurityLib\Strength::MEDIUM);
	return $factory->getGenerator($strength);
};

$container['log'] = function ($container) {
	return new \Sleeti\Logging\Logger($container);
};
