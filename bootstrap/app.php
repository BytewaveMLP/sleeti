<?php

session_start();

require __DIR__ . "/../vendor/autoload.php";

$app = new Slim\App([
	'settings' => [
		'displayErrorDetails' => true,
	],
]);

require __DIR__ . "/../app/routes.php";
