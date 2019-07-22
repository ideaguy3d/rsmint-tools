<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 8/28/2018
 * Time: 4:58 PM
 */

define('RSM_DEBUG_MODE', true);

if(!RSM_DEBUG_MODE) {
    $loanOfficerFileName = basename($_FILES['loan_officers']['name'][0]);
    $dataFileName = basename($_FILES['loan_officers']['name'][1]);
    
    $loanOfficerTargetFile = './loanOfficersInfo/' . $loanOfficerFileName;
    $dataTargetFile = './loanOfficersData/' . $dataFileName;
    
    if (move_uploaded_file($_FILES['loan_officers']['tmp_name'][0], $loanOfficerTargetFile)) {
        echo "<p>The file $loanOfficerFileName has been uploaded</p>";
    }
    else {
        echo "<h3>ERROR - file $loanOfficerFileName failed to upload </h3>";
    }
    
    if (move_uploaded_file($_FILES['loan_officers']['tmp_name'][1], $dataTargetFile)) {
        echo "<p>The file $dataFileName uploaded</p>";
    }
    else {
        echo "<h3>ERROR - $dataFileName FAILED to upload</h3>";
    }
}

// TODO: make sure all csv files get deleted afterwards

// require the LoanOfficerDelegate class
require __DIR__ . '\LoanOfficerDelegate.php';

// important directories
$loanOfficersDir = '.\loanOfficersInfo';
$dataDir = '.\loanOfficersData';

// invoke class and run algorithms
$loDel = new LoanOfficerDelegate($loanOfficersDir, $dataDir);
$loDel->runLoanOfficerDelegate();

// echo this after script is completely finished
echo "<p>Program finished processing data files.</p>";
echo "<h4><a href='other-tools.php'>Home Page</a></h4>";