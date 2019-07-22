<?php
declare(strict_types=1);
ini_set('memory_limit', '2024M');
require __DIR__ . '\vendor\autoload.php';
date_default_timezone_set("America/Los_Angeles");

// -- 0 = local env || 1 = accuzip env --
define('ACCUZIP_ENV_ASCII', 1);


//-----------------------------------------------------------------------------------------------------------------------
//-----------------------------------------------------------------------------------------------------------------------


$curTime = date('l F jS Y h:i:s A');
echo "\n\n_> time start = $curTime \n\n";

// local AccuZip folders to use
$localInput = 'C:\xampp\htdocs\accuzip';
$localOutput = 'C:\xampp\htdocs\accuzip';
$localMove = 'C:\xampp\htdocs\accuzip';

// production AccuZip folders to use
$accuzipInput = "D:\mhWork\_AUTO\_Cass\accuzip";
$accuzipOutput = "D:\mhWork\_AUTO\_Cass\accuzip";
$accuzipMove = "D:\mhWork\_AUTO\_Cass\accuzip";

if(ACCUZIP_ENV_ASCII === 1) {
    $csvInput = $accuzipInput;
    $csvOutput = $accuzipOutput;
    $csvMove = $accuzipMove;
}
else {
    $csvInput = $localInput;
    $csvOutput = $localOutput;
    $csvMove = $localMove;
}

// ad = ascii detect
$ad = new \Ninja\Client\AsciiDetect();
$csvArr = $ad->stripCsvAsciiGenerator($csvInput, $csvMove);

//-- This will be used for "Ascii Replace" functionality e.g. Ã > A
$csvArrJson = json_encode($csvArr);

$fileName = \Ninja\Client\CsvParseModel::getFileName($csvMove);

// _REPLACE "p" with "c"
$fileName = str_replace("p", "c", $fileName);

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
