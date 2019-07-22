<?php
/*
 * This file gets invoked on Auto1 and Auto2 from PowerShell after
 * AccuZip process's the CSV file. The CSV file is in a post "presort" state.
 *
 */

ini_set('memory_limit', '2024M');

//----------------------------------------
// -- 0 = local env || 1 = accuzip env --
//----------------------------------------
define('PMOD_ENV', 1);

require __DIR__ . '\vendor\autoload.php';

print("\n\n... Pmod Distribution Program is starting.\n\n");

//-- SCF Zone Data file:
$zoneDataFile = 'C:\xampp\htdocs\auto-php\csv\php2018_scf_v3.csv';
//-- post AccuZip csv file:
$varFolderPath = 'C:\xampp\htdocs\auto-php\var';

//-- Find the job number file and get job number
$varFiles = scandir($varFolderPath);
$jobNumber = '';
$jobNumberFilePath = '';
foreach($varFiles as $file) {
    if(strpos($file, 'job') !== false) {
        $jobNumberFilePath = $varFolderPath . '\\' . $file;
        $jobNumber = str_replace('job', '', $file);
        break;
    }
}

if(empty($jobNumber)) {
    //TODO: log this error to SQL Server
    exit(
        "\n\n__>> RSM_ERROR - Couldn't find job number in var folder," .
        "\n\t - PowerShell probably didn't create the file" .
        "\n\t - pmod.php line 31 ish \n\n"
    );
}

// Delete any files that are already in the var folder:
// app logic issue, I may delete the file I need :\
/*
    $filesInVarFolder = glob('C:\xampp\htdocs\auto-php\var\*');
    if(count($filesInVarFolder) > 0) {
        foreach($filesInVarFolder as $file) {
            unlink($file);
        }
    }
*/

if(PMOD_ENV === 1) {
    $fullPath2outgoing = "D:\mhWork\_Auto\_Cass\_outgoingJOB\\$jobNumber\*.csv";
}
else {
    $fullPath2outgoing = "C:\\xampp\htdocs\_outgoingJOB\\$jobNumber\*.csv";
}

$files = glob($fullPath2outgoing);

//-- Find file that contains 'final' in its' name:
$rawDataFile = '';
for($i = 0; $i < count($files); $i++) {
    $record = $files[$i];
    $final = stristr($record, 'final');
    if($final !== false) {
        $rawDataFile = $record;
        break;
    }
}


// "prep work" is just figuring out job number to figure out path to file
/****************************************
 ******** Prep work completed *********
 ****************************************/

// _Start Facility Distribution process
$pmod = new \Ninja\Auto\PmodDist($jobNumberFilePath);
// Convert the zone data to a HASH_TABLE
// JUST pass in the path to the file
$pmod->transformZoneDataCsv($zoneDataFile);
// convert the raw data to an INDEXED_ARRAY
// JUST pass in the path to the file
$pmod->transformRawDataCsv($rawDataFile);
// Add  [FAC] to header row
$pmod->setHeaderRow();

// Start the algorithm now that the csv has become an array
// distributionStart3b() will both read and write to the
// passed in $rawDataFile
echo $pmod->distributionStart($rawDataFile);
echo "\n\n";

$recordCount = $pmod->getDistributeCount();

// Create the Guzzle Client
$clientPmod = new \GuzzleHttp\Client(['base_uri' => 'https://maps.mhetadata.com/ninja/app/']);

echo "\n\nroute =\n";
echo "pmod/$recordCount/$jobNumber";
echo "\n\n";

// make request to ninja api
$clientPmod->get("pmod/$recordCount/$jobNumber");

$jobNumber = substr_replace($jobNumber, 'job', 0, 0);

// delete file in var folder
$pmod->deleteFileVarFolder();




// end of php file