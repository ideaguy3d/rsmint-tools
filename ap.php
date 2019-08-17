<?php
declare(strict_types=1);

use Ds\Vector;



echo "<br> <br><br> __>> ap";

$vector = new Vector();

$vector->push('a_vector');
$vector->push('b_vector', 'c_vector');
$vector [] = 'd_vector';

print_r($vector);


//---------------------------------------------------------------
//------------------ _Application_Start_  ----------------------
//---------------------------------------------------------------


function removeAsciiPrac () {
    $e1 = "Gonzã¡lez";
    
    for($i = 0; $i < strlen($e1); $i++){
        $char = $e1[$i];
        echo "\nscanned: $char\n";
    }
    
    echo "\n\nfinished scanning $e1";
}


$break = 'point';


//