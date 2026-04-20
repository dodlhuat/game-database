<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
// Detect base path: standard layout, subdirectory layout, or private-dir layout
$basePath = null;
foreach ([
    __DIR__.'/..',                              // standard (local Docker)
    __DIR__.'/game-database/backend',           // server: public/ beside backend/
    __DIR__.'/../../aua/game-database/backend', // server: public_html separate from private aua/
] as $candidate) {
    if (file_exists($candidate.'/vendor/autoload.php')) {
        $basePath = realpath($candidate);
        break;
    }
}

if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
