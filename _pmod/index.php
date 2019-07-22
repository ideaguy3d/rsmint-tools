<?php
declare(strict_types=1);

// composer autoload
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use \Ninja\Auto\PmodDist;
use \Ninja\Auto\CsvParseModel;

// -1 means we're NOT in debug mode
define('RSM_DEBUG_MODE', ($_GET['debug'] ?? -1));

// if host null, then PHP is in xDebug or CLI mode
// if localhost, $_SERVER["HTTP_HOST"] = 'localhost'
define('RSM_APP_ENV', ($_SERVER['HTTP_HOST'] ?? RSM_DEBUG_MODE));

//---------------------------------------------------------------------
//------------------ _Start: Application Logic  ----------------------
//---------------------------------------------------------------------

// Util fields that may get used
$time = date("h:i:sa");
$rsmTempDir = sys_get_temp_dir();

// Instance of the facility distribution algorithm
$pmod = new PmodDist('', (RSM_DEBUG_MODE !== -1));

// global script field declaration
$fileUploaded = null;
$uploadedFolderPath = null;
$uploadedFilePath = null;
$zonesFolderPath = null;
$zoneFilePath = null;
$logs = null;

// the result of the entire fac dist algorithm
$arFacDist /* as.ar */ = $fileUploaded ?? [];
// the raw CSV file from the web form
$uploadedFile /* as.ar */ = $_FILES['job-recs'] ?? [];

$arLogs = [];
$csvName = '';
$csvTemp = '';

if($uploadedFile) {
    $csvName = $uploadedFile['name'];
    $csvTemp = $uploadedFile['tmp_name'];
}

// if we ARE in debug mode
if(RSM_DEBUG_MODE !== -1) {
    echo "<h4> uploaded file info | $csvName | $csvTemp </h4>";
    var_dump($uploadedFile);
}

// _BRUTE _DEBUG: $uploadedFile = false;

// file upload
if($uploadedFile) {
    $_BRUTE_DEBUG = false;
    
    // if we ARE in debug mode
    if(RSM_DEBUG_MODE !== -1) {
        echo "<h4>In file 'job-recs' block, FILES['job-recs'] = </h4>";
        var_dump($uploadedFile);
    }
    
    // the uploaded file & the path to place it in, -1 means we're in debug mode... probably.
    if(RSM_APP_ENV === 'localhost' && !$_BRUTE_DEBUG) {
        // _BRUTE _DEBUG: $csvName = 'test-file.csv';
        $fp = './'; // folder path
        $uploadedFolderPath = $fp . 'ci/';
        $uploadedFilePath = $uploadedFolderPath . $csvName;
        $zonesFolderPath = $fp;
        $zoneFilePath = $zonesFolderPath . "zones/zones.csv";
    }
    else {
        $fp = 'C:\inetpub\wwwroot\ninja\_pmod\\';
        $uploadedFolderPath = $fp . 'ci\\';
        $uploadedFilePath = $uploadedFolderPath . $csvName;
        $zonesFolderPath = $fp;
        $zoneFilePath = $zonesFolderPath . "zones\zones.csv";
    }
    
    $fileUploaded = move_uploaded_file($csvTemp, $uploadedFilePath);
    
    if($_BRUTE_DEBUG) {
        echo "<hr> __>> app env = " . RSM_APP_ENV . "<hr>";
        $htmlFileUploadInfo = "
        <div>
            <p>File upload info:</p>
            <p>file path: $uploadedFilePath</p>
            <p>folder path: $uploadedFolderPath</p>
            <p>zone file: $zoneFilePath</p>
            <p>temporary csv: $csvTemp</p>
        </div>
        ";
        echo $htmlFileUploadInfo;
    }
    
    //var_dump($fileUploaded);
    
    // _BRUTE _DEBUG: $fileUploaded = false;
    
    if($fileUploaded) {
        try {
            // invoke csv parse class
            $logs .= '<br><i> --file uploaded correctly </i><br>';
            
            if(RSM_DEBUG_MODE !== -1) {
                foreach(scandir($uploadedFolderPath) as $file) {
                    $logs .= "<br>| $file |<br>";
                    $arLogs [] = $file;
                }
            }
            
            if(stripos($uploadedFilePath, 'csv') === false) {
                throw new Exception(
                    '__>> ERROR - FILE DID NOT GET UPLOADED, <b>Make sure you are uploading a CSV</b>'
                );
            }
            
            $arFacDist = facilityDistStart(
                $pmod, // instance of class PmodDist{}
                $zoneFilePath,
                $uploadedFilePath // the raw data
            );
        }
        catch(\Exception $e) {
            $em = $e->getMessage();
            $htmlExceptionInfo = "
                <p>please contact julius@rsmail.com and reference <i>_pmod/other-tools.php line 132</i></p>
                <p>While uploading the file this fatal error happened: $em</p>
            ";
            echo($htmlExceptionInfo);
            echo "<p>The full exception object:</p>";
            var_dump($e);
        }
        
    }
    // The file didn't upload :'(
    else {
        $htmlErrorFeedback = "
            <p style='color: red; text-align: center'>
                <b>File failed to get uploaded - $time</b>
            </p>
        ";
        // General error info
        echo($htmlErrorFeedback);
        
        echo "<p>uploaded csv file path</p>";
        var_dump($uploadedFilePath);
        echo "<p>csv temp folder =</p>";
        var_dump($csvTemp);
        echo "<p>CSV Name</p>";
        var_dump($csvName);
    }
}
else {
    $htmlError = "
        <p style='color: darkgrey; text-align: center'>
            <b>No file has been uploaded.</b>
        </p>
    ";
    
    echo $htmlError;
}

function facilityDistStart(
    PmodDist $pmod, string $zonesFilePath, string $rawDataFilePath
): array {
    
    // Convert the zone data to a HASH_TABLE
    // JUST pass in the path to the file
    $zoneHashTable = $pmod->transformZoneDataCsv($zonesFilePath);
    
    // convert the raw data to an INDEXED_ARRAY
    // JUST pass in the path to the file
    $rawDataIdxArray = $pmod->transformRawDataCsv($rawDataFilePath);
    
    // Add  [FAC] to header row
    $pmod->setHeaderRow();
    
    // Start the algorithm now that the csv has become an array
    // distributionStart3b() will both read and write to the
    // passed in $rawDataFile
    $algorithmResult = $pmod->distributionStart($rawDataFilePath);
    $facAppendedData = $pmod->getFacAppendedData();
    
    CsvParseModel::export2csv($facAppendedData, './co', 'fac-dist');
    
    return $algorithmResult;
    
} // END OF: facilityDistStart()

// _Views && $rsHost !== -1
if(RSM_DEBUG_MODE === -1 && RSM_APP_ENV !== -1) {
    $break = 'point';
    
    $view = require 'rshtml.php';
    
    // $arFacDist gets json encoded in the function
    $view(json_encode($uploadedFile), json_encode($arFacDist));
}
// WE'RE IN DEBUG MODE
else {
    $path2data = null;
    $path2zones = null;
    // in xDebug mode
    if(RSM_APP_ENV === 'localhost' || RSM_APP_ENV === -1) {
        $path2data = '.\ci';
        $path2zones = '.\zones';
    }
    else {
        $path2data = 'C:\inetpub\wwwroot\ninja\_pmod\ci';
        $path2zones = 'C:\inetpub\wwwroot\ninja\_pmod\zones';
    }
    
    
    echo "<br> <h1>__>> In Debug Mode:</h1> <br> Logs: <br>";
    var_dump($arLogs);
    
    echo "<br>";
    $results = facilityDistStart(
        $pmod, // instance of class PmodDist{}
        $path2zones . "\zones.csv",
        $path2data . "\data-file.csv"
    );
    
    // output results to browser
    echo "<br>";
    echo json_encode($results['mailing_distribution']);
    echo "<br><br>";
    echo json_encode($results['pmod_zones']);
    
    $break = 'point';
}


// END OF: php file