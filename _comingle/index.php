<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ParseCsv\Csv;


class ComingleCombine
{
    private $extracted;
    private $comingleCsv;
    
    public function startExtract() {
        // The dir to scan
        $allCsv = scandir('./test');

        //TODO: refactor to a vector
        $this->comingleCsv = [
            // field title row
            ['job_name', 'address', 'city', 'state', 'zip'],
        ];
        foreach($allCsv as $c) {
            // do not the relative symbols
            if($c !== "." && $c !== "..") {
                // use test dir to see what output csv looks like
                $csv = new Csv('./test/' . $c);
                
                // will mutate $this->extracted
                $this->extractFields($csv->data, $c);
                
                $break = 'point';
            }
            
            // free memory
            unset($csv);
        }
    }
    
    public function extractFields(array $data, string $fileName): void {
        $filename = str_replace('.csv', '', $fileName);
        
        array_walk($data, function($item, $key, $filename) {
            $extracted = [];
            $extracted [] = $filename;
            // _HARD CODED field titles
            $extracted  [] = $item['address'] ?? 'NO_ADDRESS_FIELD';
            $extracted  [] = $item['city'] ?? 'NO_CITY_FIELD';
            $extracted  [] = $item['st'] ?? 'NO_ST_FIELD';
            $extracted  [] = $item['zip'] ?? 'NO_ZIP_FIELD';
            $this->comingleCsv  [] = $extracted;
            $break = 'point';
        }, $filename);
    }
    
} // END OF: class ComingleCombine

$comingle = new ComingleCombine();

$comingle->startExtract();

$break = 'point';






// end of php file