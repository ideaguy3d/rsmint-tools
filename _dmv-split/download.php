<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 10/15/2018
 * Time: 4:45 PM
 *
 */

$file = isset($_GET['file']) ? $_GET['file'] : null;

if(!$file) {
    $m = "ERROR - query string key [file] was not set, should be" .
        " '?file=VALUE', ~download.php line 10ish";
    exit($m);
}

$localhost = "C:\\xampp\htdocs\\ninja\_dmv-split\\to\\" . $file;
$production = "C:\inetpub\wwwroot\dmv\\to\\" . $file;

// full path to file
$file = RSM_PRODUCTION_ENV ? $production : $localhost;

if(!file_exists($file)) {
    $exitMessage = "
        <h1 class='rsm-exit-message'>
            __>> ERROR - FILE NOT FOUND. No biggie, just Go to the
            <a href='/'>Landing</a> to start over.
        </h1>
    ";
    
    exit($exitMessage);
}
else {
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$file");
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: binary");
    
    readfile($file);
    unlink($file);
}

//