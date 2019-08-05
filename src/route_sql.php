<?php
declare(strict_types=1);

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Redstone\Tools\ComAutoSqlServerModel;
use Redstone\Tools\AppGlobals;

if(!empty(AppGlobals::$NINJA_AUTO_DEBUG) && AppGlobals::$NINJA_AUTO_DEBUG) {
    /*
        .17/.../street-view/user/mhetauser!@/lindsey@rsmail.com
        .17/.../comauto/start/a/{action}
    
        //-- To do a "job board data mash" sql server insert:
        .17/.../comauto/start/a/run?precision=exact&comauto-sql-insert=2
    */
    
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'POST';
}

return function(App $app) {
    
    $container = $app->getContainer();
    
    $app->get('/sql/total-by-month',
        function(Request $request, Response $response, array $args) use ($container) {
        
        }
    );
};