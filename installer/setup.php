<?php

/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

use Twig\Loader\FilesystemLoader as TwigFilesystem;
use Twig\Environment as TwigEnvironment;

use Slim\Http\Environment;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\RequestBody;
use Slim\Http\UploadedFile;
use Slim\Http\Request;
use Slim\Http\Response;

use Slim\Csrf\Guard;

use Illuminate\Database\Capsule\Manager as Capsule;

if ($argc <= 1) {
    die('No work to do\n');
}

require_once __DIR__ . '/vendor/autoload.php';

$mode = $argv[1];

$capsule = new Capsule();

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => '',
    'username'  => 'root',
    'password'  => array_values(array_slice($argv, -1))[0] ?? '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

function nginxSetup()
{
    $twig = new TwigEnvironment(new TwigFilesystem(__DIR__ . '/files/twig/'));
    $config = parse_ini_file(__DIR__ . '/nginx.config');

    foreach ($config as $property => $value) {
        if ($value === '0') {
            $config[$property] = true;
        }

        if ($value === '1') {
            $config[$property] = false;
        }
    }

    file_put_contents(__DIR__ . '/sleeti.conf', $twig->render(
        'nginxConf.twig',
        [
            'config' => $config,
            'root' => getcwd()
        ]
    ));
}

function sleetiSetup()
{
    $config = parse_ini_file(__DIR__ . '/install.config');
    $json = [];

    $config['PASSWORD-COST'] = intval($config['PASSWORD-COST']);

    if ($config['PASSWORD-COST'] <= 10) {
        $config['PASSWORD-COST'] = 15;
    }

    if ($config['PASSWORD-COST'] >= 32) {
        $config['PASSWORD-COST'] = 31;
    }

    foreach ($config as $property => $value) {
        $parts = explode('-', $property);

        $current = &$json;
        foreach ($parts as $part) {
            $current = &$current[strtolower($part)];
        }

        $current = $value;
    }

    file_put_contents(__DIR__ . '/config/config.json', json_encode($json, JSON_PRETTY_PRINT));
    file_put_contents(__DIR__ . '/config/lock', (new DateTime())->getTimestamp());
}

function sleetiConfig()
{
    require_once __DIR__ . '/bootstrap/app.php';

    $container = $app->getContainer();
    $config = parse_ini_file(__DIR__ . '/sleeti.config');

    // Tell CSRF to continue when the request fails, as it will for this form.
    $container['csrf']->setFailureCallable(function (
        Request $request,
        Response $response,
        callable $next
    ) {
        return $next($request, $response);
    });

    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $env = Environment::mock([
        'SCRIPT_NAME' => '/index.php',
        'REQUEST_URI' => '/auth/signup',
        'REQUEST_METHOD' => 'POST',
        'HTTP_CONTENT_TYPE' => 'multipart/form-data; boundary=---email',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $request = Request::createFromEnvironment($env);

    $request = $request->withParsedBody([
        'email' => $config['EMAIL'],
        'username' => $config['USERNAME'],
        'password' => $config['PASSWORD'],
        'password_confirm' => $config['PASSWORD'],
    ]);

    // Execute Request
    $response = $app($request, new Response());
    $response->getBody()->rewind();

    if (!$response->getBody()->getContents() === '') {
        exit(1);
    }
}

function sql($type, $name = '', $extra = null)
{
    if (!$type) {
        exit(1);
    }

    $schema = Capsule::schema();

    switch ($type) {
        case 'get-databases-like':
            exit(count(Capsule::select("SHOW DATABASES LIKE '$name'")));
        case 'drop-database':
            return !!Capsule::statement("DROP DATABASE IF EXISTS $name");
        case 'create-database-and-default-tables':
            file_put_contents(
                __DIR__ . '/config/config.json',
                json_encode((function () use ($name) {
                    $json = json_decode(
                        file_get_contents(__DIR__ . '/config/config.json'),
                        true
                    );

                    $json['db']['database'] = $name;
                    
                    return $json;
                })())
            );
            Capsule::statement("CREATE DATABASE IF NOT EXISTS $name DEFAULT CHARACTER SET utf8mb4");
            Capsule::unprepared("USE $name");

            $schema->create('uploaded_files', function ($table) {
                $table->bigIncrements('id');
                $table->string('filename', 100)->nullable()->default(null);
                $table->integer('privacy_state')->unsigned()->default(0);
                $table->bigInteger('owner_id')->unsigned()->nullable()->default(null);
                $table->timestamps();
            });

            $schema->create('users', function ($table) {
                $table->bigIncrements('id');
                $table->string('username', 255);
                $table->string('email', 255);
                $table->string('name', 255)->nullable()->default('имярек');
                $table->string('website', 255)->nullable()->default('https://ru.wikipedia.org/');
                $table->text('bio')->nullable()->default('Yo soy yo!');
                $table->string('password');
                $table->timestamps();
            });

            $schema->create('user_permissions', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->unsigned()->nullable()->default(null);
                $table->string('flags', 255)->default('');
                $table->timestamps();
            });

            $schema->create('user_remember_tokens', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->unsigned()->nullable()->default(null);
                $table->string('identifier', 255);
                $table->string('token', 255);
                $table->timestamp('expires');
                $table->timestamps();
            });

            $schema->create('user_password_recovery_tokens', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->unsigned()->nullable()->default(null);
                $table->string('identifier', 255);
                $table->string('token', 255);
                $table->timestamp('expires');
                $table->timestamps();
            });

            $schema->create('user_settings', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->unsigned()->nullable()->default(null);
                $table->tinyInteger('tfa_enabled')->unsigned()->default(0);
                $table->string('tfa_secret', 255)->nullable()->default(null);
                $table->integer('default_privacy_state')->unsigned()->default(0);
                $table->integer('items_per_page')->unsigned()->default(10);
                $table->timestamps();
            });

            $schema->create('user_tfa_recovery_tokens', function ($table) {
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->unsigned()->nullable()->default(null);
                $table->string('token', 255);
                $table->timestamps();
            });

            return true;
        case 'create-db-user':
            Capsule::statement("DROP USER IF EXISTS 'sleeti'@'localhost'");
            Capsule::statement("CREATE USER 'sleeti'@'localhost' IDENTIFIED BY '$extra'");
            Capsule::statement("GRANT SELECT, INSERT, DELETE, UPDATE ON $name.* TO 'sleeti'@'localhost'");

            return true;
        case 'get-admin-user':
            exit(count(Capsule::select("SELECT * FROM $name.users WHERE 'id' = 0")));
        default:
            return false;
    }
}

switch ($mode) {
    case 'nginx-setup':
        nginxSetup();
        break;
    case 'sleeti-setup':
        sleetiSetup();
        break;
    case 'sleeti-config':
        sleetiConfig();
        break;
    case 'sql':
        sql($argv[2], $argv[3] ?? '', $argv[4] ?? '');
        break;
    default:
        echo 'Command not understood.';
        break;
}

exit(0);
