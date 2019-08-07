<?php
declare(strict_types=1);

return function($app) {
    $app->options('/{routes:.+}', function($request, $response, $args) {
        return $response;
    });
    
    // cors middleware
    $app->add(function($request, $response, $next) {
        $response = $next($request, $response);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });
    
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}',
        function($req, $res) {
            $handler = $this->notFoundHandler;
            return $handler($req, $res);
        }
    );
};