<?php
declare(strict_types=1);

namespace Redstone\Tools;

use Generator;

class RsmEncodeRemove
{
    /**
     * @var string
     */
    private $path2file;
    /**
     * @var array
     */
    private $csvData;
    
    public function __construct(string $directory, string $fileName) {
        $this->path2file = $directory . DIRECTORY_SEPARATOR . $fileName;
        $this->csvData = CsvParseModel::specificCsv2array($directory, $fileName);
    }
    
    /**
     * will parse CSV data, then export the clean data to a CSV file
     */
    public function removeEncodedChars(): void {
        $break = 'point';
        
        $csvArray = $this->csvData;
        $cleanCsv = [$csvArray[0]];
        
        $start = 1;
        $limit = count($csvArray);
        $step = 1;
        $generate = function(int $start, int $limit, int $step): Generator {
            // e.g. 0 <= 10
            if($start <= $limit) {
                if($step <= 0) {
                    $info = "Generator is counting up, the step has to be greater than 0";
                    throw new \LogicException($info);
                }
                
                for($i = $start; $i < $limit; $i += $step) {
                    yield $i;
                }
            }
            // e.g. 10 <= 0
            else /* start >= limit */ {
                if($step >= 0) {
                    $info = "Generator is counting down, so step has to be negative";
                    throw new \LogicException($info);
                }
                
                for($i = $start; $i >= $limit; $i += $step) {
                    yield $i;
                }
            }
        };
        
        try {
            
            /** LOOP OVER RECORDS **/
            foreach($generate($start, $limit, $step) as $i) {
                // $record will be the csv row
                $record = $csvArray[$i];
                $cleanCsv[$i] = $record; // initialize an array
                
                /** LOOP OVER FIELDS **/
                for ($j = 0; $j < count($record); $j++) {
                    // field in the current record
                    $field = $record[$j];
                    $field2arr = str_split($field);
        
                    
                    if (preg_match('/[^\x20-\x7e]/', $field)) {
    
                        /** LOOP OVER CHARS **/
                        foreach ($field2arr as $ch) {
                            if (ord($ch) < 32 || ord($ch) > 126) {
                                array_splice($field2arr, array_search($ch, $field2arr), 1);
                            }
                        }
            
                        $record[$j] = implode("", $field2arr); // update the field
            
                    }
        
                } // END OF: looping over each field
    
                $cleanCsv[$i] = $record;
    
            }
        }
        catch (\Exception $e) {
            $exceptionMessage = $e->getMessage();
            exit("\n__>> RSM Exception: $exceptionMessage\n");
        }
        
    } // END OF: removeEncodedChars()
    
    public function getCleanFilePath(): string {
        $path2file = '';
        
        return $path2file;
    }
}