<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 10/24/2018
 * Time: 1:59 PM
 */

define("RSM_DEBUG_MODE", true);
define("RSM_PRODUCTION_ENV", false);

use Rsm\ElevateRecoveries\ElevateRecoveries;
use Rsm\ElevateRecoveries\ElrCsvParseModel;

// for now manually require interfaces rather than autoload them
require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'interfaces' . DIRECTORY_SEPARATOR . 'ElevateRecoveriesInterface.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'interfaces' . DIRECTORY_SEPARATOR . 'ElrCsvParseModelInterface.php';

// for now manually require classes rather than autoload them
require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'ElrCsvParseModel.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'ElevateRecoveries.php';

//-- primary script vars:
$csvFullPath = "";
$csvExportPath = "";
$exportedCsvFileName = "";
$downLoadLink = "";

//-- helper variables:
$localPath2glob = "C:\\xampp\htdocs\\ninja\_elevate-recoveries\ci";
$productionPath2glob = "C:\inetpub\wwwroot\\ninja\_elevate-recoveries\ci";

$localExportPath = "C:\\xampp\htdocs\\ninja\_elevate-recoveries\co";
$productionExportPath = "C:\inetpub\wwwroot\\ninja\_elevate-recoveries\co";

//-- ABSOLUTE PATH to csv file:
if(RSM_PRODUCTION_ENV) {
    //TODO: make sure this program deletes csv files in the ci folder after it completes
    $csvFullPath = $productionPath2glob;
    $csvExportPath = $productionExportPath;
}
else {
    $csvFullPath = $localPath2glob;
    $csvExportPath = $localExportPath;
}

if(!RSM_DEBUG_MODE) {
    // elr = elevate recoveries
    $elrDataTrimFileName = basename($_FILES['elr-right-shift-up-group']['name']);
    $elrTargetFile = $csvFullPath . DIRECTORY_SEPARATOR . $elrDataTrimFileName;
    $rawDataType = strtolower(pathinfo($elrTargetFile, PATHINFO_EXTENSION));
    
    echo "<br>var rawDataType = $rawDataType<br>";
    
    if(move_uploaded_file($_FILES['elr-right-shift-up-group']['tmp_name'], $elrTargetFile)) {
        echo "<p>Moved target file {$elrTargetFile}</p>";
        
        $algorithmResult = invokeElevateRecoveriesAlgorithm($csvFullPath, $csvExportPath);
        
        $exportedCsvFileName = $algorithmResult['mainData'];
        $exportedExcessFileName = $algorithmResult['excessData'];
        $currentEnv = RSM_PRODUCTION_ENV;
        
        $downLoadLink .= "&nbsp;&nbsp;<a href='download.php?file={$exportedCsvFileName}&production_env={$currentEnv}'>"
            . " Download the <b>Main</b> data {$exportedCsvFileName} </a><br><br>"
            . "&nbsp;&nbsp;<a href='download.php?file={$exportedExcessFileName}&production_env={$currentEnv}"
            . "'> Download Excess data" . $exportedExcessFileName . "</a><br><br>";
    }
    else {
        echo "<h2>ERROR - {$elrTargetFile} failed to upload</h2>";
        exit('Ending program since file did not upload');
    }
}
else {
    $elrTargetFile = $csvFullPath . DIRECTORY_SEPARATOR . 'elr-trim.csv';
    $rawDataType = strtolower(pathinfo($elrTargetFile, PATHINFO_EXTENSION));
    
    echo "\n\nMoved target file {$elrTargetFile}\n";
    echo "\nvar rawDataType = $rawDataType\n\n";
    
    $algorithmResult = invokeElevateRecoveriesAlgorithm($csvFullPath, $csvExportPath);
    
    $exportedCsvFileName = $algorithmResult['mainData'];
    $exportedExcessFileName = $algorithmResult['excessData'];
    
    // http://localhost/ninja
    $downLoadLink .= "\n\n __>> http://localhost/ninja/_elevate-recoveries/download.php?file=$exportedCsvFileName"
        . "\n __>> http://localhost/ninja/_elevate-recoveries/download.php?file=$exportedExcessFileName\n\n";
}

function invokeElevateRecoveriesAlgorithm(string $csvFullPath, string $csvExportPath): array {
    $rawCsvArr = ElrCsvParseModel::csv2array($csvFullPath);
    $elevateRecoveries = new ElevateRecoveries($rawCsvArr);
    $elevatedArr = $elevateRecoveries->elevate();
    $excessDataArr = $elevateRecoveries->getCsvExcess();
    
    return [
        'mainData' => ElrCsvParseModel::export2csv($elevatedArr, $csvExportPath, 'mainData'),
        'excessData' => ElrCsvParseModel::export2csv($excessDataArr, $csvExportPath, 'excessData'),
    ];
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Elevate Recoveries Algorithm</title>
</head>
<body>

<h1>Download link:</h1>

<?php echo $downLoadLink ?>

<p>"Elevate Recoveries Data Algorithm version 0.1"</p>

</body>
</html>




