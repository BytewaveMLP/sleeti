<?php

$app->get('/', 'HomeController:index')->setName('home');

$app->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');
$app->post('/auth/signup', 'AuthController:postSignUp');

$app->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');
$app->post('/auth/signin', 'AuthController:postSignIn');

$app->get('/auth/signout', 'AuthController:getSignOut')->setName('auth.signout');

$app->get('/auth/password/change', 'PasswordController:getChangePassword')->setName('auth.password.change');
$app->post('/auth/password/change', 'PasswordController:postChangePassword');
