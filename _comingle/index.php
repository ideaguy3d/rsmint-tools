<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ParseCsv\Csv;


class ComingleCombine
{
    private $extracted;
    
    public function startExtract() {
        $allCsv = scandir('./csv');

        //TODO: refactor to a vector
        $csvMingle = [
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
        $extracted = [];
        $filename = str_replace('.csv', '', $fileName);
        
        array_walk($data, function($item, $key, $filename) {
            $this->extracted [] = $filename;
            $this->extracted  [] = $item['address'];
            $this->extracted  [] = $item['city'];
            $this->extracted  [] = $item['state'];
            $this->extracted  [] = $item['zip'];
        }, $filename);
    }
    
} // END OF: class ComingleCombine

$comingle = new ComingleCombine();

$comingle->startExtract();

$break = 'point';






// end of php file