<?php
declare(strict_types=1);

namespace Redstone\Tools;

use ParseCsv\Csv;

class ComingleCombine
{
    private $comingleCsv;
    // if testing: 'test'
    // if a real run: 'csv'
    private $csvDir = 'csv';
    
    public function startExtract() {
        // The dir to scan
        $allCsv = scandir("./{$this->csvDir}");
        
        //TODO: refactor to a vector
        $this->comingleCsv = [
            // field title row
            ['job_name', 'address', 'city', 'state', 'zip', 'rate'],
        ];
        
        // Loop over all the CSV's
        foreach($allCsv as $key => $c) {
            // ignore relative symbols
            if($c !== "." && $c !== "..") {
                $csv = new Csv("./{$this->csvDir}/" . $c);
                
                // will mutate $this->comingleCsv
                $this->extractFields($csv->data, $c);
            }
            
            echo "\n\nscanned csv: $key\n\n";
            
            
            // free memory from buffer
            unset($csv);
            
        } // END OF: foreach looping over all CSVs in dir
        
        $csv = new Csv();
        //$headerRow = array_shift($this->comingleCsv);
        CsvParseModel::export2csv(
            $this->comingleCsv, './', 'comingle_results'
        );
        
        /*
        $csv->output(
            './csv_comingle.csv',
            $this->comingleCsv,
            $headerRow, ','
        );
        */
        
        unset($csv);
        
    } // end of: startExtract()
    
    /**
     * This function gets called inside the main loop, it'll extract
     * [address], [city], [st], [zip], and most [rate] fields
     *
     * @param array $data
     * @param string $fileName
     */
    private function extractFieldsWalk(array $data, string $fileName): void {
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
        
    }
    
    private function extractFields(array $data, string $fileName) {
        $filename = str_replace('.csv', '', $fileName);
    
        foreach($data as $key => $item) {
            // skip header row:
            if($key === 0) continue;
            
            $extracted = [];
            $extracted [] = $filename;
            $itemKeys = array_keys($item);
            $rateFields = array_filter($itemKeys, function($elem) {
                $ratePattern = "(rate|[\W]rate|[_\s-]rate[_\s-]|rate_|_rate)";
                $elem = (string)$elem;
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
                
                // dynamically add how many rate items there are
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
        }
    
    }
    
    // let web browser know how many recs were processed
    public function getTotalRecs(): string {
        $fRecs = number_format(count($this->comingleCsv));
        return $fRecs;
    }
    
} // END OF: class ComingleCombine
