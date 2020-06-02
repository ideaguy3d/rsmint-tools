<?php
/**
 * Created by PhpStorm.
 * User: julius hernandez alvarado
 * Date: 6/1/2020
 * Time: 6:48 PM
 */

namespace Redstone\Tools;


abstract class Allocadence
{
    protected string $inFolder_requiredCsv = 'csv/@required_csv';
    
    protected string $localDownloads = 'C:\Users\julius\Downloads';
    
    protected string $proDownloads = 'C:\Users\RSMADMIN\Downloads';
    
    protected string $inFileName_botSupplierInfo = 'BOT_apr2020_supplier_info.csv';
    
    /**
     * The windows downloads folder path
     */
    protected string $inFolder_downloads;
    
    protected bool $isLocal;
    
    protected array $downloadedFiles;
    
    protected array $botSupplierInfo;
    
    public function __construct() {
        $this->isLocal = AppGlobals::isLocalHost();
        
        $this->botSupplierInfo = $this->hashArray(
            CsvParseModel::specificCsv2array($this->inFolder_requiredCsv, $this->inFileName_botSupplierInfo)
        );
        
        if($this->isLocal) {
            $this->inFolder_downloads = $this->localDownloads;
        }
        else {
            $this->inFolder_downloads = $this->proDownloads;
        }
        
        // scan all the files in the windows download folder
        $this->downloadedFiles = scandir($this->inFolder_downloads);
    }
    
    /**
     * Use the values in the header row to create a hashed array
     * So basically convert an indexed array to an associative array
     *
     * @param array $indexedArrayTable - an 2D array like [['po', 'qty'],[123, 1000]]
     *
     * @return array - return an array like [['po' => 'po', 'qty'=>'qty], ['po'=>123, 'qty'=>1000]]
     */
    protected function hashArray(array $indexedArrayTable): array {
        $headerRow = $indexedArrayTable[0];
        $headerRowCount = count($headerRow);
        
        foreach($indexedArrayTable as $i => $rec) {
            $recCount = count($rec);
            
            //TODO: throw an exception
            if($recCount !== $headerRowCount) {
                $ml = __METHOD__ . ' line: ' . __LINE__;
                $rsError = "\n\n__>> RS_ERROR: header row and record are not equal in count ~$ml \n\n";
                echo $rsError;
                AppGlobals::rsLogInfo($rsError);
            }
            
            // sanitize each field in rec a bit
            foreach($rec as $idx => $val) {
                if(empty($val)) {
                    continue;
                }
                
                $rec[$idx] = trim($val);
            }
            
            $indexedArrayTable[$i] = array_combine(array_values($headerRow), $rec);
        }
        
        return $indexedArrayTable;
    }
}