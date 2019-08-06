<?php

use Slim\App;

return function(App $app) {
    
    function constructUri($db) {
        return "sqlsrv:Database={$db['dbname']};server={$db['host']}";
    }
    
    $container = $app->getContainer();
    
    // view renderer
    $container['renderer'] = function($c) {
        $settings = $c->get('settings')['renderer'];
        return new \Slim\Views\PhpRenderer($settings['template_path']);
    };
    
    // monolog
    $container['logger'] = function($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };
    
    // RSMint_1 db
    $container['dbRSMint_1'] = function($c) {
        $db = $c['settings']['dbRSMint_1'];
        $dsn = constructUri($db);
        $user = $db['user'];
        $pass = $db['pass'];
        //TODO: make sure this is follows the singleton pattern
        $pdo = new PDO($dsn, $user, $pass);
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    };
    
    // ComAuto db
    $container['dbComAuto'] = function($c) {
        $db = $c['settings']['dbComAuto'];
        $dsnComAuto = constructUri($db);
        $user = $db['user'];
        $pass = $db['pass'];
        //TODO: make sure this is follows the singleton pattern
        $pdo = new PDO($dsnComAuto, $user, $pass);
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    };
};
