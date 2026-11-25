<?php

// Like bootstrap.php, but for the MariaDB run (make test-mariadb): the same
// $_SERVER overrides, pointed at the separate game_database_testing database
// instead of SQLite, so ENUM columns, string lengths and foreign keys are
// checked the way production checks them.
$_SERVER['APP_ENV'] = 'testing';
$_SERVER['DB_CONNECTION'] = 'mysql';
$_SERVER['DB_HOST'] = 'mariadb';
$_SERVER['DB_PORT'] = '3306';
$_SERVER['DB_DATABASE'] = 'game_database_testing';
$_SERVER['DB_USERNAME'] = 'game_user';
$_SERVER['DB_PASSWORD'] = 'secret';
$_SERVER['DB_URL'] = '';
$_SERVER['MAIL_MAILER'] = 'array';
$_SERVER['QUEUE_CONNECTION'] = 'sync';
$_SERVER['SESSION_DRIVER'] = 'array';
$_SERVER['CACHE_STORE'] = 'array';

require __DIR__.'/../vendor/autoload.php';
