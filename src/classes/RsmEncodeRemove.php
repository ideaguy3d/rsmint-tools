<?php
declare(strict_types=1);

namespace Redstone\Tools;

use Generator;

class RsmEncodeRemove
{
    /**
     * @var string
     */
    private $path2directory;
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var string
     */
    private $path2file;
    /**
     * This will be an absolute path to the sanitized CSV file
     *
     * @var string
     */
    private $sanitizedFilePath;
    /**
     * @var array
     */
    private $csvData;
    private $removedEncodesInfo = [];
    
    public function __construct(string $directory, string $fileName) {
        $this->path2directory = $directory;
        $this->fileName = str_replace('.csv', '',$fileName);
        $this->path2file = $directory . DIRECTORY_SEPARATOR . $fileName;
        $this->csvData = CsvParseModel::specificCsv2array($directory, $fileName);
    }
    
    /**
     * will parse CSV data, then export the clean data to a CSV file
     */
    public function removeEncodedChars(): void {
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
                        // _ENCODE REPLACE - $ch is an encoded char so make it " "
                        else {
                            //TODO: Track which encodes were removed so AngularJS can render this info
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
        $cleanFileName = $this->fileName . '-sanitized';
        CsvParseModel::export2csv($cleanCsv, $this->path2directory, $cleanFileName);
        $this->sanitizedFilePath = $this->path2directory . DIRECTORY_SEPARATOR . $cleanFileName;
        
    } // END OF: removeEncodedChars()
    
    public function getCleanFilePath(): string {
        return $this->sanitizedFilePath;
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
            //TODO: maybe track the encoded chars here?
            $this->removedEncodesInfo [] = ['char' => $ch];
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