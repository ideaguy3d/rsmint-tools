<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 10/2/2018
 * Time: 12:14 PM
 */

cleanMultipleProperties('./ci');

/**
 * This function
 *
 * $csvPath == './folderName'
 * @param $csvPath - a string to the folder that contains the csv data to parse
 */
function cleanMultipleProperties(string $csvPath): void {
    // input CSV vars
    $path2csv = $csvPath . "/*.csv";
    
    // get all the csv files in $csvPath
    $files = glob($path2csv, GLOB_NOCHECK);
    
    // copy of the CSV into an assoc.arr
    $csv = [];
    $fieldTitleKey = "";  // field title key will get set in the loop
    
    //--------------------------
    // ---- Container loop ----
    //--------------------------
    foreach($files as $file) { // this outer loop gets file in the folder
        // EVERYTHING happens in this if statement
        if(($handle = fopen($file, 'r')) !== false) {
            $fileName = basename($file);
            echo "\n__>> file $fileName is getting processed \n";
            $count = 0;
            
            //------------------------
            // ---- inner loop 1 ----
            //------------------------
            // PARSE THE CSV FILE AND CREATE A COPY OF IT INTO the $csv ASSOC.ARR
            while(($data = fgetcsv($handle, 4096, ",")) !== false) {
                // HARD CODED [4], [5], [6], [7], [8], [9] for [OwnerFirstName] and [OwnerLastName] - RAW DATA
                $key = $data[4] . "-" . $data[5] . "-" . $data[6] . "-" . $data[7] . "-" . $data[8] . "-" . $data[9];
                
                if(!isset($csv[$key])) {
                    $count = 0;
                    $csv[$key] = $data;
                    if(!isset($csv[$fieldTitleKey])) {
                        $fieldTitleKey = $key;
                        
                        // put int 0 in the beginning of array to track
                        // how many dynamically generated fields to make
                        array_unshift($csv[$key], 0);
                    }
                }
                else {
                    // merge into 1 row code
                    $address = $data[0];
                    $city = $data[1];
                    $state = $data[2];
                    $zip = $data[3];
                    $wantedMergeData = [$address, $city, $state, $zip];
                    
                    $csv[$key] = array_merge($csv[$key], $wantedMergeData);
                    
                    // dynamic field code
                    if($count > $csv[$fieldTitleKey][0]) {
                        $dynamicFields = ["paddress$count", "pcity$count", "pstate$count", "pzip$count"];
                        $csv[$fieldTitleKey] = array_merge($csv[$fieldTitleKey], $dynamicFields);
                        $csv[$fieldTitleKey][0] = $count;
                    }
                    
                    var_dump($csv[$key]);
                    $count++;
                }
            }
            
            // quickly take out the count tracker for dynamic field generation
            if($csv[$fieldTitleKey][0] !== 'first') {
                array_shift($csv[$fieldTitleKey]);
            }
            
            //------------------------
            // ---- inner loop 2 ----
            //------------------------
            $dataDest = "./co/$fileName";
            $outputFile = fopen($dataDest, 'w') or exit("mheta - unable to open $dataDest");
            foreach($csv as $value) {
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