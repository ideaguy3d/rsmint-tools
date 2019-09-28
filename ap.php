<?php
declare(strict_types=1);

use Ds\Vector;


//---------------------------------------------------------------
//------------------ _Application_Start_  ----------------------
//---------------------------------------------------------------

// test file path to see if this works
$testSuppressed = 'C:\xampp\htdocs\tools\uploads\test\suppressed_test.csv';
$testSuppressed = str_replace('\\', '/', $testSuppressed);

$testRemoved = 'C:\xampp\htdocs\tools\uploads\test\removed_test.csv';
$testRemoved = str_replace('\\', '/', $testRemoved);

$files = [$testSuppressed, $testRemoved];
//C:\xampp\htdocs\tools\uploads\test\
$zipTestName = 'C:\xampp\htdocs\tools\uploads\test\suppression.zip';
$zip = new ZipArchive();
$zip->open($zipTestName, ZipArchive::CREATE);
foreach($files as $file) {
    $fileName = basename($file);
    $zip->addFile($file, $fileName);
}
$zip->close();


echo "<br> <br> __>> ap: <br><br>";

$vector = new Vector();

$vector->push('a_vector');
$vector->push('b_vector', 'c_vector');
$vector [] = 'd_vector';

print_r($vector);

function removeAsciiPrac () {
    $e1 = "Gonzã¡lez";
    
    for($i = 0; $i < strlen($e1); $i++){
        $char = $e1[$i];
        echo "\nscanned: $char\n";
    }
    
    echo "\n\nfinished scanning $e1";
}


$break = 'point';


// end of php file