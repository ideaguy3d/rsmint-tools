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
            ['job_name', 'address', 'city', 'state', 'zip', 'rate'],
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
        $headerRow = array_shift($this->comingleCsv);
        $csv->output(
            './csv_comingle.csv', $this->comingleCsv,
            $headerRow, ','
        );
        
        unset($csv);
    }
    
    /**
     * This function gets called inside the main loop, it'll extract
     * [address], [city], [st], [zip], and most [rate] fields
     *
     * @param array $data
     * @param string $fileName
     */
    public function extractFields(array $data, string $fileName): void {
        $filename = str_replace('.csv', '', $fileName);
        
        array_walk($data, function($item, $key, $filename) {
            $extracted = [];
            $count = 0;
            $extracted [] = $filename;
            $itemKeys = array_keys($item);
            $rateFields = array_filter($itemKeys, function($elem) {
                $ratePattern = "([\W]rate|[_\s-]rate[_\s-]|rate_|_rate)";
                $rateMatch = preg_match("/$ratePattern/i", $elem);
                return ($rateMatch === 1);
            });
            // _HARD CODED field titles
            $extracted  [] = $item['address'] ?? 'NO_ADDRESS_FIELD';
            $extracted  [] = $item['city'] ?? 'NO_CITY_FIELD';
            $extracted  [] = $item['st'] ?? 'NO_ST_FIELD';
            $extracted  [] = $item['zip'] ?? 'NO_ZIP_FIELD';
            if(count($rateFields) > 0) {
                $rateStr = "";
                foreach($rateFields as $rate) {
                    $itemRate = $item[$rate];
                    $rateStr .= "[$rate] = $itemRate | ";
                }
                $extracted  [] = $rateStr;
            }
            else {
                $extracted  [] = 'NO_RATE_FIELD';
            }
            $this->comingleCsv  [] = $extracted;
            //echo "\n\n$filename record <$count> extracted\n";
            $break = 'point';
        }, $filename);
        
        $break = 'point';
    }
    
} // END OF: class ComingleCombine
