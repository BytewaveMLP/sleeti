<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

// Register our global middleware
$app->add(new \Sleeti\Middleware\LogPageViewMiddleware($container));
$app->add(new \Sleeti\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \Sleeti\Middleware\OldInputMiddleware($container));
$app->add(new \Sleeti\Middleware\SessionCanaryMiddleware($container));
$app->add(new \Sleeti\Middleware\ActiveRouteMiddleware($container));
$app->add(new \Sleeti\Middleware\RememberMeMiddleware($container));
