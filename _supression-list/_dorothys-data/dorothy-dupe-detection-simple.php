<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 10/1/2018
 * Time: 12:02 PM
 */

require '..\vendor\autoload.php';

use Ninja\Auto\CsvParseModel;

// base data set
$csvBasePath = '.\base';
// dupe check list
$csvDupePath = '.\dupe';

$arrCsvBase = CsvParseModel::getCsvArray($csvBasePath);
$arrCsvDupe = CsvParseModel::getCsvArray($csvDupePath);
$addedDupeFieldIndex = count($arrCsvBase[0]);
//-- Add the [duplicate_record] field title to base data:
$arrCsvBase[0][$addedDupeFieldIndex] = 'duplicate_fullname_record';

// HARD CODED SOLUTION TO GET QUICK SOLUTION
// dupe data: [9]last & [10]first
// base data: [9]last & [10]first
for($di = 1; $di < count($arrCsvDupe); $di++) {
    $dupeRecord = $arrCsvDupe[$di];
    $fullNameDupe = trim($dupeRecord[9]) . " " . trim($dupeRecord[10]);
    echo "\n\n__>>Starting inner loop\n\n";
    
    //-----------------------
    // ---- INNER LOOP -----
    //-----------------------
    for($bi = 1; $bi < count($arrCsvBase); $bi++) {
        $baseRecord = $arrCsvBase[$bi];
        $fullNameBase = trim($baseRecord[9]) . " " . trim($baseRecord[10]);
        $dupeIndexSet = isset($arrCsvBase[$bi][$addedDupeFieldIndex]);
        if($fullNameBase === $fullNameDupe) {
            $arrCsvBase[$bi][$addedDupeFieldIndex] = 'dupe';
            echo "\n <<  dupe found >> \n";
            //break;
        }
        else if($dupeIndexSet && $arrCsvBase[$bi][$addedDupeFieldIndex] !== 'dupe') {
            $arrCsvBase[$bi][$addedDupeFieldIndex] = '';
        }
    }
}

echo "\n\n__>> Starting CSV export \n\n";
if(($handle = fopen('./dupe_field_added.csv', 'w')) !== false) {
    foreach($arrCsvBase as $row) {
        fputcsv($handle, $row);
    }
    
    // REMEMBER: close the file stream (:
    fclose($handle);
    echo "\n\n__>> SUCCESS - File has finished processing.\n\n";
}
else {
    echo "\n\n__>> ERROR - File didn't process. CsvParseModel.php line 25 ish\n\n";
}

echo "\nbreakpoint\n";