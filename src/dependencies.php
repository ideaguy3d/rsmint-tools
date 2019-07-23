<?php

use Slim\App;

return function (App $app) {
    
    $container = $app->getContainer();

    // view renderer
    $container['renderer'] = function ($c) {
        $settings = $c->get('settings')['renderer'];
        return new \Slim\Views\PhpRenderer($settings['template_path']);
    };

    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };
    
    // RSMint_1 db
    $container['dbRSMint_1'] = function($c){
        $dbRSMint_1 = $c['settings']['dbRSMint_1'];
        $dsn = "sqlsrv:Database={$dbRSMint_1['dbname']};server={$dbRSMint_1['host']}";
        $user = $dbRSMint_1['user'];
        $pass = $dbRSMint_1['pass'];
        $pdo = new PDO($dsn, $user, $pass);
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    };
};
