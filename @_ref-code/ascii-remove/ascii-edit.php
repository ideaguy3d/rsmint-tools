<?php
declare(strict_types=1);
ini_set('memory_limit', '2024M');
require __DIR__ . '\vendor\autoload.php';
date_default_timezone_set("America/Los_Angeles");


//-----------------------------------------------------------------------------------------------------------------------


$csvInput = null;
$csvOutput = null;
$csvMove = null;
$iniFilePath = null;

$currentHost = $_SERVER['COMPUTERNAME'];
$localEnv = (stripos($currentHost, 'RSMAUTO') >= 0);

$localInput = 'C:\xampp\htdocs\AccuZIP6 5.0';
$localOutput = 'C:\xampp\htdocs\AccuZIP6 5.0';
$localMove = 'C:\xampp\htdocs\AccuZIP6 5.0';

$accuzipInput = "D:\mhWork\_AUTO\_Cass\accuzip";
$accuzipOutput = "D:\mhWork\_Auto\_Cass\accuzip";
$accuzipMove = "D:\mhWork\_AUTO\_Cass\accuzip";

$localIniFilePath = "C:\\xampp\htdocs\AccuZIP6 5.0\Scripts\Presorts\Statements\_automh_pwk.ini";
$accuzipIniFilePath = "";

if(!$localEnv) {
    $iniFilePath = $accuzipIniFilePath;
}
else {
    $iniFilePath = $localIniFilePath;
}

// acsii detect class instance
$ad = new \Ninja\Client\AsciiDetect();
$csvArr = $ad->stripCsvAsciiGenerator($csvInput, $csvMove);

// _SPECIAL - detect if this job is a Nationwide Marketing job
$rsmParseIni = new \Ninja\Client\RsmParseIni($iniFilePath);
$iniRes = $rsmParseIni->returnIniParseResult();

if($iniRes['client_name'] === 'Nationwide Marketing') {
    // invoke node.js and crawl job board
    /*
        // example of how it looks in PowerShell
        Start-Process C:\xampp\php\php.exe C:\xampp\htdocs\auto-php\ascii-edit.php
    */
    shell_exec("path/to/node.exe");
}

//-- This will be used for "Ascii Replace" functionality e.g. Ã > A
$csvArrJson = json_encode($csvArr);

$fileName = \Ninja\Client\CsvParseModel::getFileName($csvMove);
$fileName = str_replace("p", "", $fileName);

$jobId = strstr($fileName, ".", true);

if(\Ninja\Client\CsvParseModel::putCsvArray($csvMove, $fileName, $csvArr)) {
    echo "\n\n Ascii Chars removed from CSV Successfully \n\n";
}
else {
    echo "\n\n ERROR - problem with CsvParseModel::putCsvArray() \n\n";
}

$results = $ad->getTotalCharsRemoved();
$v1 = 1;
$v2 = 2;

// production = https://maps.mhetadata.com/ninja/app/
// localhost = http://localhost/ninja/app/
$client = new \GuzzleHttp\Client(['base_uri' => 'https://maps.mhetadata.com/ninja/app/']);

//-- new way:
$client->get("ascii/r/$v2/$jobId/{$results['fieldsParsed']}/{$results['totalCharsRemoved']}");

$curTime = date('l F jS Y h:i:s A');

echo "\n\n _> time end = $curTime \n\n";





//