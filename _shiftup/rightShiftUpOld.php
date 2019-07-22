<?php

echo "<br><h1>Redstone Print & Mail</h1><br><hr>";

// phpinfo();

$dbConnect = isset($_GET["db"]) ? $_GET["db"] : null;
$dbConnectTrueIA = ['1', 'y', 'true', 'yes'];
$action = isset($_GET["action"]) ? $_GET["action"] : null;

/*********** DEBUGGING **********/
$action = 'csv2';
/*********** DEBUGGING **********/

// Query SQL Server
if ($action === "db") {
    try {
        $conn = new PDO("sqlsrv:Server=192.168.7.16\RSMAUTO;Database=rsmauto", "mhetadata", "miguel");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (Exception $e) {
        die(print_r($e->getMessage()));
    }
    
    $tsql = "SELECT TOP(100) * FROM pracMailData2";
    $getResults = $conn->prepare($tsql);
    $getResults->execute();
    $resultSet = $getResults->fetchAll(PDO::FETCH_BOTH);
    
    foreach ($resultSet as $row) {
        echo "<strong>" . $row['_first_'] . "</strong> lives in <b>"
            . $row['_countynm__'] . "</b>";
        echo '<br>';
    }
}
// parse CSV data files
else if ($action === 'csv') {
    $csvPath = './csvI';
    $csvVersion = 1;
    outputParsedCsv1($csvPath, $csvVersion);
}
// let user user use multiple ways to basically say 'yes'
else if (in_array($dbConnect, $dbConnectTrueIA, true) === true) {
    echo "<p>array_search() = </p>";
    echo in_array($dbConnect, $dbConnectTrueIA);
    echo "<h1>array_search() worked ! ^_^</h1>";
    echo "<h2>dbConnect = $dbConnect</h2>";
}
// --------------------------------------------------------
// This is the "right shift up grouping" algorithm I wrote
// for Richard roughly around the week of 6-10-2018
// --------------------------------------------------------
else if ($action === 'csv2') {
    cleanMultipleProperties('./ci');
}
else {
    echo "<h1>Dang, nothing worked :\</h1>";
}

/**
 * $csvPath == './folderName'
 * @param $csvPath - a string to the folder that contains the csv data to parse
 */
function cleanMultipleProperties($csvPath) {
    // input CSV vars
    $path2csv = $csvPath . "/*.csv";
    // get all the csv files in $csvPath
    $files = glob($path2csv, GLOB_NOCHECK);
    
    // copy of the CSV into an assoc.arr
    $csv = [];
    $tempTracker = [];
    $count = 0;
    $fieldTitleKey = "";  // field title key will get set in the loop
    
    /**
     * PARSE THE CSV FILE AND CREATE A COPY OF IT INTO the $csv ASSOC.ARR
     */
    foreach ($files as $file) {
        if (($handle = fopen($file, 'r')) !== false) {
            $fileName = basename($file);
            echo "\n file $fileName is getting processed \n";
            $count = 0;
            
            //----------------
            // - inner loop -
            //----------------
            while (($data = fgetcsv($handle, 4096, ",")) !== false) {
                //$csv []= $data;
                // $key = $data[5] . "-" . $data[2]; // heinz dupes
                $key = $data[0] . "-" . $data[1]; // week 6-10-2018 dupes
                if (!isset($csv[$key])) {
                    $count = 0;
                    $csv[$key] = $data;
                    if ($csv[$key][0] === "first" && $csv[$key][1] === "last") {
                        $fieldTitleKey = $key;
                        array_unshift($csv[$key], 0);
                    }
                }
                else {
                    // merge into 1 row code
                    $wantedMergeData = [$data[8], $data[9], $data[10], $data[11]];
                    $csv[$key] = array_merge($csv[$key], $wantedMergeData);
                    
                    // dynamic field code
                    if ($count > $csv[$fieldTitleKey][0]) {
                        $dynamicFields = ["paddress$count", "pcity$count", "pstate$count", "pzip$count"];
                        $csv[$fieldTitleKey] = array_merge($csv[$fieldTitleKey], $dynamicFields);
                        $csv[$fieldTitleKey][0] = $count;
                    }
                    
                    var_dump($csv[$key]);
                    $count++;
                }
            }
            
            // quickly take out the count tracker for dynamic field generation
            if ($csv[$fieldTitleKey][0] !== 'first') {
                array_shift($csv[$fieldTitleKey]);
            }
            
            //----------------
            // - inner loop -
            //----------------
            $dataDest = "./co/$fileName";
            $outputFile = fopen($dataDest, 'w') or exit("mheta - unable to open $dataDest");
            foreach ($csv as $value) {
                fputcsv($outputFile, $value);
            }
            
            fclose($outputFile);
            fclose($handle);
            $csv = []; // clear out csv of old data
        }
        else {
            echo "<h1>other-tools.php line 128 ish - error opening file $file</h1>";
        }
    }
}

function outputParsedCsv1($csvPath, $csvVersion) {
    $patternToMatch = "$csvPath$csvVersion/*.csv";
    echo "Pattern To Match = $patternToMatch";
    // $files = glob("$csvPath$csvVersion/*.csv"); // use variables
    $files = glob($patternToMatch, GLOB_NOCHECK); // {1}
    if ($files === $patternToMatch) { // match failed, notify user
        echo "there was something wrong with the pattern :(";
    }
    else {
        foreach ($files as $file) {
            if (($handle = fopen($file, 'r')) !== false) { // {2}
                echo "<br><br><h2>The Filename: " . basename($file) . "</h2><br><br>"; // {3}
                while (($data = fgetcsv($handle, 4096, ",")) !== false) { // {4}
                    echo implode("\t", $data); // to output all data from each CSV file
                }
                echo "<br>";
                fclose($handle); // {5}
            }
            else {
                echo "Could not open file: " . $file;
            }
        }
    }
}