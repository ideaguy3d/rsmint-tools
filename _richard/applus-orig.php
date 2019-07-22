<?php
/**
 * This applus-orig file only works from the CLI
 * it does not upload a file from the web browser
 * so it doesn't download the completed file afterward
 *
 * This will only work if files are manually put in the correct folders
 */

declare(strict_types=1);
// composer autoload wires this script up to the ninja api
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use \Ninja\Auto\RsmStopWatch;
use \Ninja\Auto\CsvParseModel;

// CACHE MODES = parse-txt || php-file
define('APPLUS_CACHE_MODE', 'parse-txt');

$pathToTxt = './ti/';
$rawDmvTxt = file_get_contents(glob($pathToTxt . '*.txt')[0]);
$rows = preg_match_all("/.*\n/", $rawDmvTxt, $matches);
$rawData = $matches[0];
$normalizedData = [];
$applusPhpData = 'applus-php-data';
$localInFolder = substr_replace($pathToTxt, '', -1);

echo "\n\n__>> Start Time: 0.00 " . RsmStopWatch::start() . "\n";

// _BRUTE _DEBUG: sort of caching data
if(APPLUS_CACHE_MODE === 'parse-txt') {
    // I'm forcing a data cache by creating a CSV after, I have to remember
    // to check the 'ti' folder to see if "rs_applus_php_data.csv" exists and is current
    $normalizedData /* idx.ar */ = applusParse($rawData);
    
    // cache data by making it a CSV file at this point so that while debugging I do not
    // have to invoke applusParse() over and over again.
    CsvParseModel::export2csv($normalizedData, $localInFolder, $applusPhpData);
}
else if(APPLUS_CACHE_MODE === 'php-file') {
    $normalizedData = require_once './data1applus.php';
}
// There is no speed increase by converting the csv to an array
else {
    $normalizedData = CsvParseModel::specificCsv2array(
        $localInFolder, "rs_$applusPhpData.csv"
    );
    
    // strip each field of "double quotes"
    array_walk_recursive($normalizedData, function(&$value, $key) {
        if(strpos($value, '"') !== false) {
            $value = trim($value, '"');
        }
    });
}

echo "\n\n__>> End Time: " . RsmStopWatch::elapsed() . "\n\n";
echo "... Now doing custom Applus Operations against data set.\n\n";

// transform data
applusOps($normalizedData);

echo "... Exporting transformed data set to CSV.";

// applus data now has custom fields w/custom ops appended to it
$csvName = CsvParseModel::export2csv(
    $normalizedData, './to', 'applus-data'
);

echo "\n\n__>> exported data to $csvName\n";


//-----------------------------------------------------------------------------------------
//-------------------------------- SCRIPT _UTIL FUNCTIONS --------------------------------
//-----------------------------------------------------------------------------------------

function applusOps(array &$data) {
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
    
    $z = 'br';
    
} //END OF: applusOps()

/**
 * Although I'm using a bunch of fancy programming techniques with this function
 * the underlying design is flawed because I'm attempting to parse every single
 * char in the raw data row which decreases the time complexity O(n^2)
 *
 * @param array $rawData
 *
 * @return array
 */
function slowApplusParse(array $rawData): array {
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
    $ti = 0; // title idx
    $activeField = $headerRow[$ti];
    
    //---------------------
    //---- <CLOSURE/> ----
    //---------------------
    $sanitInit = function(string &$fieldValue, array &$fields, string $field, int $row) use (
        &$rowsNormalized, $activeField
    ): void {
        // sanitize field
        $fieldValue = trim($fieldValue);
        $fieldValue = preg_replace("/\s{2,}/", ' ', $fieldValue);
        
        // initialize normalized array
        $rowsNormalized[$row][$field] = $fieldValue;
        $fieldValue = '';
        
        // ensure count > max
        $fields[$field][0]++;
    };
    
    // outer loop over raw globbed rows, skip 1st row
    for($i = 1; $i < count($rawData); $i++) {
        // loop variables
        $row = $rawData[$i];
        $fieldValue = '';
        
        // INNER loop over raw chars in row
        for($j = 0; $j < strlen($row); $j++) {
            $char = $row[$j];
            
            if(
                $activeField === 'make' || $activeField === 'model' ||
                $activeField === 'primary owner' || $activeField === 'secondary owner' ||
                $activeField === 'address' ||
                // add char to field value if it's not whitespace
                preg_match("/\s/", $char) === 0
            ) {
                $fieldValue .= $char;
            }
            
            if(
                $activeField && $fields[$activeField][0] < $fields[$activeField][1]
            ) {
                $fields[$activeField][0]++;
            }
            else if(
                $activeField && $fields[$activeField][0] === $fields[$activeField][1]
            ) {
                $sanitInit($fieldValue, $fields, $activeField, $rowCount);
                $activeField = $headerRow[++$ti] ?? null;
                if(!$activeField) continue;
                $z = 'break';
            }
            
        } // END OF: inner looping over each chars in the raw globbed row
        
        // break before going to next row
        $zbreak = 'point';
        
        // go to the next row
        $rowCount++;
        $ti = 0;
        $activeField = $headerRow[$ti];
        
        foreach($fields as $key => $val) {
            // reset count
            $fields[$key][0] = 0;
        }
        
        if($i % 500 === 0) {
            $iFormat = number_format($i);
            echo "\nparsed $iFormat records\n";
        }
        
    } // END OF: outer looping over raw txt file
    
    return $rowsNormalized;
}

/**
 * This function makes use of the substr() function which decreases the
 * time complexity to O(n*15),
 * ... 15 because that is how many fields have to get extracted from the
 * raw .txt file
 *
 * @param array $rawData
 *
 * @return array
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
        
        // break before going to next row
        $zbreak = 'point';
        
        if($i % 500 === 0) {
            $iFormat = number_format($i);
            echo "\n-- parsed $iFormat records\n";
        }
        
    } // END OF: outer looping over raw txt file
    
    $totalParsed = number_format(count($rowsNormalized));
    echo "\n---- Parsed $totalParsed total records\n";
    
    // insert header row
    array_unshift($rowsNormalized, $headerRow);
    
    return $rowsNormalized;
}