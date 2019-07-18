<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function(App $app) {
    
    $container = $app->getContainer();
    
    $app->get('/encode-remove',
        function(Request $request, Response $response, array $args) use ($container) {
            $container->get('renderer')->render($response, 'encode-remove.phtml', $args);
        }
    );
    
    /**
     * Root route to The API for the slim micro framework, this won't be used much to be honest
     */
    $app->get('/[{name}]',
        
        // route controller
        function(Request $request, Response $response, array $args) use ($container) {
            
            // Sample log message
            $container->get('logger')->info("\n\rRedstone '/' route\n\r");
            
            // Render index view
            return $container->get('renderer')->render($response, 'index.phtml', $args);
            
        } // END OF: root route ctrl i.e. "/[{optional-route-var}]"
    
    );
    
};
