<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 10/26/2018
 * Time: 6:34 PM
 */

$file = isset($_GET['file']) ? $_GET['file'] : null;
$productionEnv = isset($_GET['production_env']) ? $_GET['production_env'] : null;

if(!$file) exit("ERROR - query string key [file] was not set, should be '?file=VALUE', ~ download.php line 10ish");

$localhost = "C:\\xampp\htdocs\\ninja\_elevate-recoveries\co\\" . $file;
$production = "C:\inetpub\wwwroot\\ninja\_elevate-recoveries\co\\" . $file;

$file = $productionEnv ? $production : $localhost;

if(!file_exists($file)) {
    exit("<h1>__>> ERROR - FILE [ $file ] NOT FOUND. Just go to homepage.</h1>");
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