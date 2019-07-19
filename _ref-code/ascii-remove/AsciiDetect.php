<?php
/**
 * Created by PhpStorm.
 * User: Mike
 * Date: 7/31/2018
 * Time: 9:41 AM
 */

namespace Ninja\Client;

class AsciiDetect implements IAsciiDetect
{
    public $results;
    
    public function __construct() {
        $this->results = [
            'fieldsParsed' => 0,
            'totalCharsRemoved' => 0,
        ];
    }
    
    //TODO: Improve the runtime of this algorithm
    // an example path:
    // C:\xampp\htdocs\_z-accuzip
    public function stripCsvAscii(string $fullPathToCsv): array {
        $csvArray = CsvParseModel::getCsvArray($fullPathToCsv);
        
        $cleanCsv = [$csvArray[0]]; // header row
        $limit = count($csvArray);
        
        // <CLOSURE> for the generator
        $row = function ($start, $limit) {
            for ($i = $start; $i < $limit; $i++) {
                yield $i;
            }
        };
        
        //--------------------
        //-- LOOP over rows --
        //--------------------
        // start $i = 1 to skip the header row
        foreach ($row(1, $limit) as $i) {
            // $record will be the csv row
            $record = $csvArray[$i];
            $cleanCsv[$i] = $record; // initialize an array
            
            //----------------------
            //-- LOOP over fields --
            //----------------------
            for ($j = 0; $j < count($record); $j++) {
                // $j is field index
                $field = $record[$j]; // create copy of field
                $field2arr = str_split($field);
                
                if (preg_match('/[^\x20-\x7e]/', $field)) {
                    
                    //--------------------------
                    //-- LOOP over characters --
                    //--------------------------
                    foreach ($field2arr as $ch) {
                        if (ord($ch) < 32 || ord($ch) > 126) {
                            array_splice($field2arr, array_search($ch, $field2arr), 1);
                        }
                    }
                    
                    $record[$j] = implode("", $field2arr); // update the field
                    
                }
                
            } // END OF: looping over each field
            
            $cleanCsv[$i] = $record;
            
        } // END OF: outer g-looping over each csv record
        
        return $cleanCsv;
    }
    
    public function getTotalCharsRemoved(): array {
        return $this->results;
    }
    
    public function stripCsvAsciiGenerator(string $fullPathToCsv, string $move = null): array {
        if ($move) {
            // 1st, move the file
            $newPath = $this->moveFile($fullPathToCsv, $move);
            // 2nd, place an informative file in watch folder so humans don't delete them... hopefully.
            // $this->placeInformativeFile($fullPathToCsv);
        }
        else {
            $newPath = $fullPathToCsv;
        }
        
        // now begin the process
        $i = 0;
        $cleanCsv = [];
        
        //-- This removes encoded ascii chars:
        //-------------------------
        //-- GENERATE over rows --
        //-------------------------
        foreach (CsvParseModel::readCsvGenerator($newPath) as $record) {
            $cleanCsv[$i] = $record; // initialize an array
            if (is_array($record)) {
                //-----------------------
                //-- LOOP over fields --
                //-----------------------
                for ($j = 0; $j < count($record); $j++) {
                    // $j is field index
                    $field = $record[$j]; // create copy of field
                    $field2arr = str_split($field);
                    $this->results['fieldsParsed']++;
                    // if chars in field have char codes NOT in hexadecimal 20-7f (regular letters,
                    // numbers, symbols) search for and destroy encoded ascii chars
                    if (preg_match('/[^\x20-\x7f]/', $field)) {
                        //--------------------------
                        //-- LOOP over characters --
                        //--------------------------
                        foreach ($field2arr as $ch) {
                            if (ord($ch) < 32 || ord($ch) > 127) {
                                array_splice($field2arr, array_search($ch, $field2arr), 1);
                                $this->results['totalCharsRemoved']++;
                            }
                        }
                        $record[$j] = implode("", $field2arr); // update the field
                    }
                }
            }
            
            $cleanCsv[$i] = $record;
            $i++;
        }
        
        return $cleanCsv;
    }
    
    private function moveFile(string $inputPath, string $movePath): string {
        $fullPathToCsv = glob($inputPath . '\*.csv')[0];
        $fileName = basename($fullPathToCsv);
        if (strpos($fileName, "p")) {
            $newPath = "$movePath\\$fileName";
            rename($fullPathToCsv, $newPath);
            
            return $newPath;
        }
        else {
            exit("__>> ERROR - Can only process csv with a 'p' character (which indicates 'php7' code will process this data)");
        }
    }
    
    private function placeInformativeFile(string $path): void {
        $informativeFileName = '\PHP is processing the data - PLEASE do not delete this file.txt';
        $path .= $informativeFileName;
        
        $handle = fopen($path, 'w')
        or exit ("make-txt.php - ERROR: could not create file");
        
        $info = <<< phpDataInfo

            PHP is currently invoking 3rd party APIs,
            this process can take like 10-20 mins to complete
            so this file is just to prevent the job board
            from putting more data in this watch folder
            while the 3rd party APIs are processing our
            data. PHP will delete this file after the
            process is complete so that the Job Board
            can continue to put data in this watch folder.

            ~ Thanks ^_^  ... Julius.
phpDataInfo;
        // ^ <<< heredoc
        
        fwrite($handle, $info);
        fclose($handle);
    }
    
    public function stripJsonAscii(array $csvArray): array {
        $cleanCsv = [
            $csvArray[0], // header row
        ];
        
        $limit = count($csvArray);
        
        // <CLOSURE> for the generator
        $row = function ($start, $limit) {
            for ($i = $start; $i < $limit; $i++) {
                yield $i;
            }
        };
        
        //--------------------
        //-- LOOP over rows --
        //--------------------
        // start $i = 1 to skip the header row
        foreach ($row(1, $limit) as $i) {
            // $record will be the csv row
            $record = $csvArray[$i];
            $cleanCsv[$i] = $record; // initialize an array
            
            //----------------------
            //-- LOOP over fields --
            //----------------------
            for ($j = 0; $j < count($record); $j++) {
                // $j is field index
                $field = $record[$j]; // create copy of field
                $field2arr = str_split($field);
                
                if (preg_match('/[^\x20-\x7f]/', $field)) {
                    //--------------------------
                    //-- LOOP over characters --
                    //--------------------------
                    foreach ($field2arr as $ch) {
                        if (ord($ch) < 32 || ord($ch) > 127) {
                            array_splice($field2arr, array_search($ch, $field2arr), 1);
                        }
                    }
                    $record[$j] = implode("", $field2arr); // update the field
                }
            }
            
            $cleanCsv[$i] = $record;
        }
        
        return $cleanCsv;
    }
    
    public function getRawCsv(string $fullPath): array {
        $rawCsv = CsvParseModel::getCsvArray($fullPath);
        return $rawCsv;
    }
    
    private function simpleAsciiExample() {
        $ascii1 = "Ï¿½esme";
        $asciiSet = [
            ['Ïota', 'what¿', 'ha½lf'],
            ['Šuper', 'Žulu', 'Ÿup'],
        ];
        $ascii1arr = [
            "Ï",
            '¿',
            '½',
        ];
        echo "\n\n val 1 = " . ord($ascii1arr[0]);
        echo "\n\n val 2 = " . chr(36);
        echo "\n\n val 2 = " . chr(ord($ascii1arr[0]));
        echo "\n\n";
    }
}