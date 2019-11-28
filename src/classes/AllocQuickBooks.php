<?php
declare(strict_types=1);


namespace Redstone\Tools;

/**
 * Class AllocQuickBooks as of 11-27-2019 this class is SUPER HARDCODED and not meant to be
 * used by anyone other than me.
 *
 * @package Redstone\Tools
 */
class AllocQuickBooks
{
    private $downloadsFolder;
    
    public function __construct() {
        $localDownloads = 'C:\Users\julius\Downloads';
        $proDownloads = 'C:\Users\RSMADMIN\Downloads';
        $isLocal = AppGlobals::isLocalHost();
        
        if($isLocal) {
            $this->downloadsFolder = $localDownloads;
        }
        else {
            $this->downloadsFolder = $proDownloads;
        }
    }
    
    /**
     * Map Allocadence Purchase Orders to QuickBooks
     */
    public function qbPurchaseOrderMap() {
        $poFileName = 'inboundexportbydate';
        $downloadedFiles = scandir($this->downloadsFolder);
        // array of full paths to each po file downloaded from Allocadence
        $poFilesArray = [];
        $poUnion = [];
        $c = 0;
        
        // get each downloaded po file from Allocadence
        foreach($downloadedFiles as $file) {
            $isPoFile = (strpos($file, $poFileName) !== false);
            if($isPoFile) {
                $poFilesArray [] = "$file";
            }
        }
        
        // convert each downloaded PO file to an array and UNION them
        foreach($poFilesArray as $poFile) {
            $poArray = CsvParseModel::specificCsv2array($this->downloadsFolder, $poFile);
    
            // get header row real quick
            if($c === 0) {
                $poUnion [] = $poArray[0];
                $c++;
            }
    
            // get rid of header row real quick for the UNION
            array_shift($poArray);
    
            // _INNER LOOP to UNION all the po arrays
            foreach($poArray as $po) {
                $poUnion [] = $po;
            }
    
            $break = 'point';
        }
    }
    
    /**
     * Map Allocadence Receiving to QuickBooks
     */
    public function qbReceivingMap() {
        $break = 'point';
    }
}