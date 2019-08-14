<?php


namespace Redstone\Tools;

use ParseCsv\Csv;


class ComingleCombine
{
    private $extracted;
    private $comingleCsv;
    private $csvDir = 'csv';
    
    public function startExtract() {
        // The dir to scan
        $allCsv = scandir("./{$this->csvDir}");
        
        //TODO: refactor to a vector
        $this->comingleCsv = [
            // field title row
            ['job_name', 'address', 'city', 'state', 'zip'],
        ];
        foreach($allCsv as $c) {
            // do not the relative symbols
            if($c !== "." && $c !== "..") {
                
                //echo "\n__>> Loading CSV into memory\n";
                
                // use test dir to see what output csv looks like
                $csv = new Csv("./{$this->csvDir}/" . $c);
                
                // will mutate $this->extracted
                $this->extractFields($csv->data, $c);
                
                $break = 'point';
            }
            
            // free memory from buffer
            unset($csv);
        } // END OF: foreach looping over all CSVs in dir
        
        $csv = new Csv();
        $headerRow = array_unshift($this->comingleCsv);
        $csv->output(
            './csv_comingle.csv', $this->comingleCsv,
            $headerRow, ','
        );
        
        unset($csv);
    }
    
    public function extractFields(array $data, string $fileName): void {
        $filename = str_replace('.csv', '', $fileName);
        
        array_walk($data, function($item, $key, $filename) {
            $extracted = [];
            $count = 0;
            $extracted [] = $filename;
            // _HARD CODED field titles
            $extracted  [] = $item['address'] ?? 'NO_ADDRESS_FIELD';
            $extracted  [] = $item['city'] ?? 'NO_CITY_FIELD';
            $extracted  [] = $item['st'] ?? 'NO_ST_FIELD';
            $extracted  [] = $item['zip'] ?? 'NO_ZIP_FIELD';
            $this->comingleCsv  [] = $extracted;
            //echo "\n\n$filename record <$count> extracted\n";
            $break = 'point';
        }, $filename);
    }
    
} // END OF: class ComingleCombine
