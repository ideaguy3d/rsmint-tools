<?php

/**
 * master branch
 */



ini_set('memory_limit', '512M');

if(PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    
    if(is_file($file)) return false;
}
 
echo "<br/>/../vendor/autoload.php";

require __DIR__ . '/../vendor/autoload.php';



// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';

 echo "<br/> 7 Ello World ^_^/";



 //
