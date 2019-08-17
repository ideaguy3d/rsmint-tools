<?php
declare(strict_types=1);

use Ds\Vector;


//---------------------------------------------------------------
//------------------ _Application_Start_  ----------------------
//---------------------------------------------------------------


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