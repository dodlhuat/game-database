<?php

// PHPUnit's <env force="true"> updates putenv()/$_ENV but not $_SERVER.
// Laravel's env() reads $_SERVER first (via Dotenv's ServerConstAdapter),
// so Docker container env vars would otherwise always win.
// Setting them here before the autoloader ensures the test overrides take effect.
$_SERVER['APP_ENV'] = 'testing';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = ':memory:';
$_SERVER['DB_URL'] = '';
$_SERVER['MAIL_MAILER'] = 'array';
$_SERVER['QUEUE_CONNECTION'] = 'sync';
$_SERVER['SESSION_DRIVER'] = 'array';
$_SERVER['CACHE_STORE'] = 'array';

require __DIR__.'/../vendor/autoload.php';
