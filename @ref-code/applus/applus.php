<?php
/**
 * This is the file that will be used for raw .txt upload
 * of Applus data, after PHP finishes processing a CSV
 * will get downloaded. This file includes a User Interface
 * 
 */
declare(strict_types=1);

setcookie("ApplusStatus", 'ready');

// composer autoload wires this script up to the ninja api
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use \Ninja\Auto\CsvParseModel;
use \Ninja\Auto\RsmStopWatch;

$rsHost = $_SERVER['HTTP_HOST']; //  ?? $debugMode;

// CACHE MODES = parse-txt || php-file
define('APPLUS_CACHE_MODE', 'parse-txt');
define('RSM_APP_ENV', $rsHost);

$uploadedFile = $_FILES['job-recs'] ?? null;
$csvName = '';
$csvTemp = '';
$uploadedFilePath = '';
$csvName = '';

if(!$uploadedFile) {
    applusView();
}
else {
    $csvName = $uploadedFile['name'];
    $csvTemp = $uploadedFile['tmp_name'];
    
    if(RSM_APP_ENV === 'localhost') {
        // _BRUTE _DEBUG: $csvName = 'test-file.csv';
        $fp = './'; // folder path
        $uploadedFolderPath = $fp . 'ti/';
        $uploadedFilePath = $uploadedFolderPath . $csvName;
    }
    else {
        $fp = 'C:\inetpub\wwwroot\ninja\_richard\\';
        $uploadedFolderPath = $fp . 'ti\\';
        $uploadedFilePath = $uploadedFolderPath . $csvName;
    }
    
    $fileUploaded = move_uploaded_file($csvTemp, $uploadedFilePath);
    
    $pathToTxt = $uploadedFilePath; // . DIRECTORY_SEPARATOR . $csvName; //'./ti/'
    
    //$rawDmvTxt = file_get_contents(glob($pathToTxt . '*.txt')[0]);
    $rawDmvTxt = file_get_contents($pathToTxt);
    $rows = preg_match_all("/.*\n/", $rawDmvTxt, $matches);
    $rawData = $matches[0];
    $normalizedData = [];
    $applusPhpData = 'applus-php-data';
    
    // make "./ti/" > "./ti"
    $localInFolder = substr_replace($pathToTxt, '', -1);
    $localOutFolder = './to';
    
    $startTime = RsmStopWatch::start();
    setcookie("ApplusStatus", "Start Time: $startTime");
    
    if(APPLUS_CACHE_MODE === 'parse-txt') {
        // ~1.39sec runtime for 22,000+ recs
        $normalizedData /* idx.ar */ = applusParse($rawData);
    }
    else if(APPLUS_CACHE_MODE === 'php-file') {
        // This is basically instantaneous (~0.03s to be exact)
        $normalizedData = require_once './data1applus.php';
    }
    
    $endTime = RsmStopWatch::elapsed();
    
    setcookie("ApplusStatus", "End Time: $endTime");
    setcookie("ApplusStatus","Now doing custom Applus Operations against data set.");
    
    // transform data
    applusOps($normalizedData);
    
    setcookie("ApplusStatus","Exporting transformed data set to CSV");
    
    // applus data now has custom fields w/custom ops appended to it
    $csvName = CsvParseModel::export2csv(
        $normalizedData, $localOutFolder, 'applus-data'
    );
    
    setcookie("ApplusStatus","exported data to $csvName");
    
    $path2applusDataFile = __DIR__ . DIRECTORY_SEPARATOR . 'to' . DIRECTORY_SEPARATOR . $csvName;
    
    setcookie("ApplusStatus", "done");
    
    if(file_exists($path2applusDataFile)) {
        header("Location: ./applus-download.php");
    }
}


//-----------------------------------------------------------------------------------------
//-------------------------------- SCRIPT _UTIL FUNCTIONS --------------------------------
//-----------------------------------------------------------------------------------------

/**
 * This will render the view. The view is being required
 * as a function to make explicitly define scope in the
 * view file
 */
function applusView(): void {
    $year = date("Y");
    $rsCopy = "<h4>Redstone Print and Mail &copy; $year</h4>";
    
    $view = require 'applus-view.php';
    
    $view($rsCopy);
}

/**
 * After the raw .txt file has been parsed, do the custom
 * calculations to the special fields.
 *
 * @param array $data
 *
 */
function applusOps(array &$data): void {
    // all zips need 0's prepended if zip < 5 digits
    $fieldCityStateZip = null;
    // first and last get reversed if not a company (LLC, INC, ETC.)
    $fieldFirst = null;
    // just the [secondary_owner] field transcribed to a new field, will pascal case it though
    $fieldFirst2 = null;
    $bizAbbrev = null;
    $baseBizAbbrev = ['lc', 'llc', 'pllc', 'corp', 'inc', 'pc', 'co', 'ltd'];
    
    function addNewFieldsToHeaderRow(array &$data): void {
        $data[0] [] = 'city_state_zip';
        $data[0] [] = 'first';
        $data[0] [] = 'first2';
    }
    
    function constructRegex(array $bizAbbrev): array {
        $bizRegex = [];
        
        // construct the regex pattern for each base biz abbreviation
        for($i = 0, $j = 0; $i < count($bizAbbrev); $i++, $j += 2) {
            $offset = $i + $j;
            $abbrev = $bizAbbrev[$i];
            $bizRegex[$offset] = "/[\W\.\s]{$abbrev}[\W\.\s]/";
            $bizRegex[$offset + 1] = "/[\W\.\s]{$abbrev}\.?/";
            $bizRegex[$offset + 2] = "/[\W\.\s]{$abbrev}/";
        }
        
        return $bizRegex;
    }
    
    $bizAbbrev = constructRegex($baseBizAbbrev);
    
    function makeCityStZip(string $city, string $st, string $zip): string {
        // get zip+4
        $zip4 = substr($zip, -4, 4);
        if(empty($zip4)) {
            $break = 'point';
            return "ERROR - debug at applus.php line 105 ish";
        }
        // get zip5
        $zip5 = stristr($zip, $zip4, true);
        $formattedZip = strlen($zip5) < 5 ? str_pad($zip5, 5, "0") : $zip5;
        
        $fieldCityStateZip = "$city, $st $formattedZip-$zip4";
        
        return $fieldCityStateZip;
    }
    
    $businessCheck = function(string $primaryOwner) use ($bizAbbrev, $baseBizAbbrev): bool {
        $primaryOwner = strtolower($primaryOwner);
        
        for($i = 0; $i < count($bizAbbrev); $i++) {
            $pattern = $bizAbbrev[$i];
            
            $bizContain = (preg_match($pattern, $primaryOwner) === 1);
            
            if($bizContain) {
                //-- EDGE CASES:
                // 'mcmahon connor', 'Diamond colleen', 'hinckley david',
                // 'avt construction inc', 'the new haven comp inc'
                // check for edge cases:
                foreach($baseBizAbbrev as $abbrev) {
                    if($abbrev !== 'lc' && $abbrev !== 'llc') {
                        if(preg_match("/$abbrev\w+/", $primaryOwner) === 1) {
                            // do a hard match for 'inc'
                            if(preg_match("/[\s]inc\.?/", $primaryOwner) === 1) {
                                return true;
                            }
                            return false;
                        }
                        else if(preg_match("/\w+$abbrev/", $primaryOwner)) {
                            return false;
                        }
                    }
                }
                return true;
            }
            
        } // END OF: looping over biz regex patterns
        
        return false;
    };
    
    function makeFirst(bool $isBiz, string $primaryOwner, int $rowNum): string {
        $fieldFirst = $isBiz ? $primaryOwner : "ERROR - primary owner = $primaryOwner debug at applus.php line 99 ish";
        
        if(!$isBiz) {
            $_1stSpace = strpos($primaryOwner, ' ');
            if(is_bool($_1stSpace)) {
                $fieldFirst = ucwords($primaryOwner);
                $z = 'br';
            }
            else {
                $lastName = substr($primaryOwner, 0, $_1stSpace);
                $fieldFirst = str_replace($lastName, '', $primaryOwner);
                $fieldFirst = "$fieldFirst $lastName";
                $fieldFirst = ucwords($fieldFirst);
            }
            
        }
        else if(strpos($fieldFirst, 'ERROR') > -1) {
            $check = "what the error is >:/";
        }
        else {
            $check = "to make sure it's a biz";
        }
        
        return $fieldFirst;
        
    } // END OF: makeFirst()
    
    // skip header row, loop over raw data
    for($i = 1; $i < count($data); $i++) {
        $row = $data[$i];
        $primaryOwner = ucfirst(strtolower($row['primary_owner']));
        $isBiz = $businessCheck($primaryOwner);
        
        // basic fields and ops
        $city = ucfirst(strtolower($row['city']));
        $st = $row['st'];
        $zip = $row['zip'];
        
        if(empty($city) || empty($st) || empty($zip) || empty($primaryOwner)) {
            continue;
        }
        
        // append dynamically created fields
        $data[$i]['city_state_zip'] = makeCityStZip($city, $st, $zip);
        $data[$i]['first'] = makeFirst($isBiz, $primaryOwner, $i);
        $data[$i]['first2'] = ucwords(strtolower($row['secondary_own']));
        
        // just for debugging, looking at a single row is a Lot less data to analyze
        $row = $data[$i];
        
        $z = 'br';
        
    } // END OF: looping over each row in ref data
    
    addNewFieldsToHeaderRow($data);
    
} //END OF: applusOps()

/**
 * This function makes use of the substr() function which decreases the
 * time complexity to O(n*15),
 * ... 15 because that is how many fields have to get extracted from the
 * raw .txt file
 *
 * @param array $rawData
 *
 * @return array
 *
 */
function applusParse(array $rawData): array {
    // function declarations
    $headerRow = null;
    // initialize the array that will contain rows with fields
    $rowsNormalized = [];
    // track which row for $dataNormalized we're on
    $rowCount = 0;
    
    $fields = [
        // field => [count, max]
        'vin' => [0, 16],
        'class_code' => [0, 1],
        'license' => [0, 7],
        'mail_by_date' => [0, 7],
        'expiration' => [0, 7],
        'mailer_due' => [0, 7],
        'year' => [0, 3],
        'make' => [0, 17],
        'model' => [0, 17],
        'data_of_birth' => [0, 7],
        'primary_owner' => [0, 37],
        'secondary_own' => [0, 37],
        'address' => [0, 37],
        'city' => [0, 21],
        'st' => [0, 1],
        'zip' => [0, 9],
    ];
    $headerRow = array_keys($fields);
    
    // loop over raw globbed rows, skip 1st row
    for($i = 1; $i < count($rawData); $i++) {
        // loop variables
        $row = $rawData[$i];
        $fieldValue = null;
        
        // INNER LOOP: worst case = 15
        foreach($fields as $key => $val) {
            if(isset($lastField)) {
                $length = ($fields[$key][1] + 1);
                $rowsNormalized[$rowCount][$key] = substr($row, 0, $length);
                $fieldValue = trim($rowsNormalized[$rowCount][$key]);
                $lastField = $rowsNormalized[$rowCount][$key];
                
                // optional - WRAP EACH VALUE IN "double quotes" SO EXCEL WON'T EDIT IT
                //$rowsNormalized[$rowCount][$key] = "\"$fieldValue\"";
                $rowsNormalized[$rowCount][$key] = "$fieldValue";
                
                // decrease the size of the raw globbed chars in $row
                // e.g. "xyz12 3abc..." > "12 3abc..." example decreased char count by 3
                $row = substr_replace($row, '', 0, strlen($lastField));
            }
            else {
                $length = ($fields[$key][1] + 1);
                $rowsNormalized[$rowCount][$key] = substr($row, 0, $length);
                $fieldValue = $rowsNormalized[$rowCount][$key];
                $lastField = $rowsNormalized[$rowCount][$key];
                
                // optional - WRAP EACH VALUE IN "double quotes" SO EXCEL WON'T EDIT IT
                //$rowsNormalized[$rowCount][$key] = "\"$fieldValue\"";
                $rowsNormalized[$rowCount][$key] = "$fieldValue";
                
                // decrease the size of the raw globbed chars in $row
                // e.g. "xyz12 3abc..." > "12 3abc..." example decreased char count by 3
                $row = substr_replace($row, '', 0, strlen($lastField));
            }
            
        } // END OF: inner looping over raw chars in globbed .txt data
        
        $rowCount++;
        
    } // END OF: outer looping over raw txt file
    
    // insert header row
    array_unshift($rowsNormalized, $headerRow);
    
    return $rowsNormalized;
}