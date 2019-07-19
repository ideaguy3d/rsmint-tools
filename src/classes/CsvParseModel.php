<?php
declare(strict_types=1);

namespace Redstone\Tools;

use Redstone\Tools\Interfaces\ICsvParseModel;

class CsvParseModel implements ICsvParseModel
{
    public function __construct() { }
    
    public static function getCsvArray(string $path): array {
        $csvFileFolder = glob("$path/*.csv");
        $csvFile = $csvFileFolder[0];
        $csv = [];
        $count = 0;
        
        if(($handle = fopen($csvFile, 'r')) !== false) {
            while(($data = fgetcsv($handle, 8096, ",")) !== false) {
                $csv[$count] = $data;
                ++$count;
            }
            fclose($handle);
        }
        
        return $csv;
    }
    
    // THIS IS AN EXACT COPY OF "public static function getCsvArray()"
    public static function csv2array(string $path): array {
        $csvFileFolder = glob("$path\*.csv");
        $csvFile = $csvFileFolder[0];
        $csv = [];
        $count = 0;
        
        if(($handle = fopen($csvFile, 'r')) !== false) {
            while(($data = fgetcsv($handle, 8096, ",")) !== false) {
                $csv[$count] = $data;
                ++$count;
            }
            fclose($handle);
        }
        return $csv;
    }
    
    public static function specificCsv2array(string $path2folder, string $csvName): array {
        $csvFile = "$path2folder\\$csvName";
        $csv = [];
        $count = 0;
        
        if(($handle = @fopen($csvFile, 'r')) !== false) {
            while(($data = fgetcsv($handle, 8096, ",")) !== false) {
                $csv[$count] = $data;
                ++$count;
            }
            fclose($handle);
        }
        
        return $csv;
    }
    
    public static function export2csv(
        array $dataSet, string $exportPath, string $name2giveFile
    ): string {
        $csvName = 'rs_' . $name2giveFile . '.csv';
        $exportPath = $exportPath . DIRECTORY_SEPARATOR . $csvName;
        $outputFile = fopen($exportPath, 'w') or exit("mheta - unable to open $exportPath");
        
        foreach($dataSet as $value) {
            fputcsv($outputFile, $value);
        }
        
        fclose($outputFile);
        
        return $csvName;
        
    } // END OF: export2csv
    
    public static function export2csvNoRename(string $path, array $arr2export): string {
        if(($handle = fopen($path, 'w')) !== false) {
            foreach($arr2export as $row) {
                fputcsv($handle, $row);
            }
            
            // REMEMBER: close the file stream (:
            fclose($handle);
            return "\n\n__>> SUCCESS - File has finished processing.\n\n";
        }
        else {
            return "\n\n__>> ERROR - File didn't process. CsvParseModel.php line 25 ish\n\n";
        }
    }
    
} // END OF: class CsvParseModel