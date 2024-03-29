<?php
declare(strict_types=1);

namespace Redstone\Tools;

use Generator;
use PDO;

class EncodeRemove
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
     * @var string
     */
    private $sanitizedFilePath;
    /**
     * @var array
     */
    private $csvData;
    /**
     * will insert all the encoded chars to SQL Server
     * @var array
     */
    private $removedEncodesInfo = [];
    /**
     * connection to RSMint_1 db
     * @var PDO
     */
    private $dbRSMint_1;
    /**
     * @var string
     */
    private $removedEncodesTable = '[RSMint_1].[dbo].[RemovedEncodes]';
    /**
     * The randomly generated id AngularJS will send in the Q string
     * to uniquely identify which encodes to query for
     * @var string
     */
    private $AngularJS_id;
    
    public function __construct(
        string $directory, string $fileName, PDO $dbRSMint_1, string $AngularJS_id
    ) {
        $this->path2directory = $directory;
        $this->dbRSMint_1 = $dbRSMint_1;
        $this->fileName = str_replace('.csv', '', $fileName);
        $this->path2file = $directory . DIRECTORY_SEPARATOR . $fileName;
        $this->csvData = CsvParseModel::specificCsv2array($directory, $fileName);
        $this->AngularJS_id = $AngularJS_id;
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
                // $i is basically the row
                $row = $i;
                $firstField = '';
                
                /** LOOP OVER FIELDS **/
                for($f = 0; $f < count($record); $f++) {
                    // field in the current record
                    $field = trim($record[$f]);
                    $multiSpacePattern = "/\s{2,}/";
                    $matchMultiSpace = preg_match($multiSpacePattern, $field);
                    if($matchMultiSpace === 1) {
                        preg_replace($multiSpacePattern, ' ', $field);
                    }
                    $cleanField = '';
                    // $f is basically the column
                    $column = $f;
                    if($f === 0) {
                        $firstField = $field;
                    }
                    // preg_match('/[^\x20-\x7e]/', $field)
                    
                    /** LOOP OVER EACH CHAR **/
                    for($c = 0; $c < strlen($field); $c++) {
                        $ch = $field[$c];
                        
                        // _ENCODE REPLACE
                        if(!$this->isEncodedChar($ch, $row, $column, $firstField)) {
                            $cleanField .= $ch;
                        }
                        //else {$cleanField .= "";}
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
    
    /**
     * REMEMBER: this is where the encoded chars get tracked
     *  as of "7-22-2019@7:15pm" there are 2 separate locations encodes get appended.
     * each so-called "WAVE" should append to $removedEncodesInfo when it detects a
     * char is encoded.
     *
     * @param string $ch
     * @param int $row
     * @param int $column
     * @param string $firstField
     *
     * @return bool
     */
    public function isEncodedChar(
        string $ch, int $row, int $column, string $firstField
    ): bool {
        $isEncoded = false;
        // okay chars "[,\.\/\-_]" means ,./-_
        $goodChars = "/([a-z]|[A-Z]|[0-9]|[,\.\/\-_#])/";
        $match = preg_match($goodChars, $ch);
        $matchSpace = preg_match("/\s/", $ch);
        // uphold DRY principle
        $trackEncoded = function() use ($ch, $row, $column, $firstField) {
            $this->removedEncodesInfo [] = [
                'file' => $this->fileName,
                'encode' => $ch,
                'row' => $row,
                'column' => $column,
                'first_field' => $firstField
            ];
        };
        //TODO: check for escape encodes e.g. "\t"
    
        
        if(ord($ch) < 32 || ord($ch) > 126) {
            $trackEncoded();
            return ($isEncoded = true);
        }
        // the char is not encoded
        else if(ord($ch) > 32 || ord($ch) <= 126) {
            return $isEncoded;
        }
        
        if($match === 1 || $matchSpace === 1) {
            return $isEncoded;
        }
        else if($match === 0) {
            $trackEncoded();
            return($isEncoded = true);
        }
        else if($match === false) {
            exit("\n __>> ERROR - can't match, the char = $ch\n");
        }
        
        return $isEncoded;
        
    } // END OF: isEncodedChar()
    
    public function insertIntoSqlServer(): void {
        $query = "
            INSERT INTO {$this->removedEncodesTable}
            (
                 [rsm_file_name]
                ,[rsm_row]
                ,[rsm_column]
                ,[first_field]
                ,[encode2]
                ,[angularjs_id]
            )
            VALUES
            (
                :rsmFileName,
                :rsmRow,
                :rsmColumn,
                :firstField,
                :encode,
                :angularjsId
            )
        ";
        
        try {
            $statement = $this->dbRSMint_1->prepare($query);
            
            // loop over all the tracked encoded chars
            for($e = 0; $e < count($this->removedEncodesInfo); $e++) {
                $record = $this->removedEncodesInfo[$e];
                
                // fields to bind to
                $rsmFileName = $record['file'];
                $rsmRow = $record['row'];
                $rsmColumn = $record['column'];
                $firstField = $record['first_field'];
                $encode = ord($record['encode']);
                
                // bind values
                $statement->bindValue(':rsmFileName', $rsmFileName);
                $statement->bindValue(':rsmRow', $rsmRow);
                $statement->bindValue(':rsmColumn', $rsmColumn);
                $statement->bindValue(':firstField', $firstField);
                $statement->bindValue(':encode', $encode);
                $statement->bindValue(':angularjsId', $this->AngularJS_id);
                
                // execute sql query
                $statement->execute();
                
                $break = 'point';
            }
            
            $break = 'point';
        }
        catch(\Exception $e) {
            $message = $e->getMessage();
            exit('__>> ERROR - SQL Server insert broke: ' . $message);
        }
    }
    
}