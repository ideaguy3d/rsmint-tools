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
                for($f = 0; $f < count($record); $f++) {
                    // field in the current record
                    $field = $record[$f];
                    $cleanField = '';
                    
                    // preg_match('/[^\x20-\x7e]/', $field)
                    
                    /** LOOP OVER EACH CHAR **/
                    for($c = 0; $c < strlen($field); $c++) {
                        $ch = $field[$c];
                        
                        if(!$this->isEncodedChar($ch)) {
                            $cleanField .= $ch;
                        }
                        // $ch is an encoded char so make it " "
                        else {
                            $cleanField .= " ";
                        }
                    }
                    
                    $record[$f] = trim($cleanField);
                    
                } // END OF: looping over each field
                
                $cleanCsv[$i] = $record;
                
            } // END OF: looping over each record
        }
        catch(\Exception $e) {
            $exceptionMessage = $e->getMessage();
            exit("\n__>> RSM Exception: $exceptionMessage\n");
        }
        
        $break = 'point';
        
        // remove file name, this is a full path, not a relative path
        $exportFolder = str_replace(basename($this->path2file), '', $this->path2file);
        // remove trailing '\' at the end of the string
        $exportFolder = substr($exportFolder, 0, strlen($exportFolder)-1);
        CsvParseModel::export2csv(
            $cleanCsv, $exportFolder, 'rsm-encode-remove'
        );
        
    } // END OF: removeEncodedChars()
    
    public function getCleanFilePath(): string {
        $path2file = '';
        
        return $path2file;
    }
    
    public function isEncodedChar(string $ch): bool {
        $isEncoded = false;
        $goodChars = "/([a-z]|[A-Z]|[0-9])/";
        $match = preg_match($goodChars, $ch);
        
        //TODO: Detect 1 whitespace
        
        if($match === 1) {
            return $isEncoded;
        }
        else if($match === 0) {
            echo "\n encoded char = $ch";
        }
        else if($match === false) {
            exit("\n __>> ERROR - can't match, the char = $ch\n");
        }
        
        if(ord($ch) < 32 || ord($ch) > 126) {
            return ($isEncoded = true);
        }
        
        return $isEncoded;
        
    } // END OF: isEncodedChar()
    
}