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

use Sleeti\Middleware\AuthMiddleware;
use Sleeti\Middleware\GuestMiddleware;
use Sleeti\Middleware\TesterMiddleware;
use Sleeti\Middleware\ModeratorMiddleware;
use Sleeti\Middleware\AdminMiddleware;
use Sleeti\Middleware\CsrfViewMiddleware;
use Sleeti\Middleware\NotInstalledMiddleware;
use Sleeti\Middleware\TwoFactorAuthSetupMiddleware;
use Sleeti\Middleware\TwoFactorAuthFullMiddleware;
use Sleeti\Middleware\TwoFactorAuthPartialMiddleware;

// ugly af grouping
$app->group('', function() use ($container) { // it's groups all the way down
	$this->group('', function() use ($container) {
		$this->group('', function() use ($container) {
			$this->get('/', 'HomeController:index')->setName('home');

			$this->get('/user/{id}', 'ProfileController:viewProfile')->setName('user.profile');

			$this->get('/viewfile/{filename}', 'FileController:viewFile')->setName('file.view');

			$this->get('/community', 'CommunityController:community')->setName('community');

			$this->group('', function() use ($container) {
				$this->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');
				$this->post('/auth/signup', 'AuthController:postSignUp');

				$this->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');
				$this->post('/auth/signin', 'AuthController:postSignIn');
			})->add(new GuestMiddleware($container));

			$this->group('', function() use ($container) {
				$this->get('/auth/signout', 'AuthController:getSignOut')->setName('auth.signout');

				$this->get('/auth/password/change', 'PasswordController:getChangePassword')->setName('auth.password.change');
				$this->post('/auth/password/change', 'PasswordController:postChangePassword');

				$this->group('/profile', function() use ($container) {
					$this->get('/edit', 'ProfileController:getEditProfile')->setName('user.profile.edit');
					$this->post('/edit', 'ProfileController:postEditoProfile');

					$this->get('/delete[/{id}]', 'AuthController:getDeleteAccount')->setName('user.profile.delete');
					$this->post('/delete/{id}', 'AuthController:postDeleteAccount');

					$this->group('/2fa', function() use ($container) {
						$this->get('', 'TwoFactorAuthController:getEnable')->setName('user.profile.2fa');
						$this->post('', 'TwoFactorAuthController:postEnable');

						$this->group('', function() {
							$this->get('/setup', 'TwoFactorAuthController:getSetup')->setName('user.profile.2fa.setup');
							$this->post('/setup', 'TwoFactorAuthController:postSetup');
						})->add(new TwoFactorAuthSetupMiddleware($container));
					});
				});

				$this->group('/upload', function() {
					$this->get('', 'FileController:getUpload')->setName('file.upload');
					$this->post('', 'FileController:postUpload');

					$this->get('/paste', 'FileController:getPaste')->setName('file.upload.paste');
					$this->post('/paste', 'FileController:postPaste');

					$this->get('/sharex', 'FileController:getSharex')->setName('file.upload.sharex');

					$this->get('/bash', 'FileController:getBashScript')->setName('file.upload.bash');
				});

				$this->get('/delete/{filename}', 'FileController:deleteFile')->setName('file.delete');

				$this->group('/admin', function() use ($container) {
					$this->group('/acp', function() {
						$this->get('', 'AcpController:getAcpHome')->setName('admin.acp.home');

						$this->get('/database', 'AcpController:getDatabaseSettings')->setName('admin.acp.database');
						$this->post('/database', 'AcpController:postDatabaseSettings');

						$this->get('/site', 'AcpController:getSiteSettings')->setName('admin.acp.site');
						$this->post('/site', 'AcpController:postSiteSettings');

						$this->get('/password', 'AcpController:getPasswordSettings')->setName('admin.acp.password');
						$this->post('/password', 'AcpController:postPasswordSettings');

						$this->get('/errors', 'AcpController:getErrorSettings')->setName('admin.acp.errors');
						$this->post('/errors', 'AcpController:postErrorSettings');

						$this->get('/recaptcha', 'AcpController:getReCaptchaSettings')->setName('admin.acp.recaptcha');
						$this->post('/recaptcha', 'AcpController:postReCaptchaSettings');
					});

					$this->group('/user', function() {
						$this->get('/giveperms/{uid}', 'AdminController:getAddPermissionsPage')->setName('admin.user.giveperms');
						$this->post('/giveperms/{uid}', 'AdminController:postAddPermissionsPage');
					});
				})->add(new AdminMiddleware($container));

				$this->group('/mod', function() use ($container) {
					$this->group('/mcp', function() {
						$this->get('', 'McpController:getMcpHome')->setName('mod.mcp.home');
						$this->get('/files', 'McpController:getFiles')->setName('mod.mcp.files');
					});
				})->add(new ModeratorMiddleware($container));
			})->add(new AuthMiddleware($container));

			$this->group('', function() use ($container) {
				$this->get('/install', 'InstallController:getInstall')->setName('install');
				$this->post('/install', 'InstallController:postInstall');
			})->add(new NotInstalledMiddleware($container));
		})->add(new TwoFactorAuthFullMiddleware($container));

		$this->group('', function() use ($container) {
			$this->get('/auth/signin/2fa', 'AuthController:get2Fa')->setName('auth.signin.2fa');
			$this->post('/auth/signin/2fa', 'AuthController:post2Fa');

			$this->get('/auth/signin/2fa/cancel', 'AuthController:get2FaCancel')->setName('auth.signin.2fa.cancel');
		})->add(new GuestMiddleware($container))->add(new TwoFactorAuthPartialMiddleware($container));
	})->add(new CsrfViewMiddleware($container));
})->add($container['csrf']);

// No CSRF protection for ShareX uploads
// TODO: upload tokens instead of user creds
$app->post('/upload/sharex', 'FileController:sharexUpload');
