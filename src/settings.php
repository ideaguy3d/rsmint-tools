<?php

return [
    
    'settings' => [
        
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'redstone-app',
            //isset($_ENV['docker']) ? 'php://stdout' :
            'path' => 'logs\app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        'dbRSMint_1' => [
            'host' => '192.168.7.16\\rsmauto',
            'dbname' => 'RSMint_1',
            'user' => 'mhetadata',
            'pass' => 'miguel'
        ],
        
        'dbComAuto' => [
            'host' => '192.168.7.16\\rsmauto',
            'dbname' => 'ComAuto',
            'user' => 'mhetadata',
            'pass' => 'miguel'
        ],
        
    ],
    
];
