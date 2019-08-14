<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ParseCsv\Csv;

$allCsvs = scandir('./csv');

//TODO: refactor to a vector
$csvMingle = [
    // field title row
    ['job_name', 'address', 'city', 'state', 'zip']
];

foreach($allCsvs as $c) {
    // do not the relative symbols
    if($c !== "." && $c !== "..") {
        $csv = new Csv('./csv/'.$c);
        $csvMingle [] = extractFields($csv['data'], $c);
    }
    // free memory
    unset($csv);
}

function extractFields (array $data, string $fileName): array {
    $extracted = [];
    $fileName = str_replace('.csv', '', $fileName);
    
    return $extracted;
}




$break = 'point';






// end of php file