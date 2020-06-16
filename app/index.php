<?php
/**
 * master branch
 */

ini_set('memory_limit', '2048M');
date_default_timezone_set('America/Los_Angeles');

if(PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    
    if(is_file($file)) return false;
}

require __DIR__ . '/../vendor/autoload.php';

//-- starting a session causes an internal server error 🤔
//session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($app);

// Register middleware
$middleware = require __DIR__ . '/../src/middleware.php';
$middleware($app);

// Register routes
$routes_sql = require __DIR__ . '/../src/routes_sql.php';
$routes_sql($app); // sql
$routes_base = require __DIR__ . '/../src/routes.php';
$routes_base($app); // base
$cors_route = require __DIR__ . '/../src/cors_route.php';
$cors_route($app); // cors

// Run app
try {
    $app->run();
} catch(Throwable $e) {
    $ml = __METHOD__ . ' line: ' . __LINE__;
    $infoMes = 'RS_FATAL_ERROR: an exception has bubbled up to root scope:';
    $errMes = $e->getMessage();
    exit ("\n\n __>> $infoMes ~$ml  \n\n $errMes \n\n");
}
