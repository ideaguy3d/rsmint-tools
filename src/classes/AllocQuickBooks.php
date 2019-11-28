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
        // each po file downloaded from Allocadence
        $poFilesArray = [];
        $poUnion = [];
        $field = null;
        $c = 0;
        // qb maps
        $qbHeaderRowStr = "Vendor,Transaction Date,PO Number,Item,Quantity,Description,Rate";
        $qbHeaderRow = explode(",", $qbHeaderRowStr);
        $qbMap = [$qbHeaderRow];
        
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
                $field = $this->poFindKeys($poArray[0]);
                $c++;
            }
            
            // get rid of header row real quick for the UNION
            array_shift($poArray);
            
            foreach($poArray as $po) {
                $poUnion [] = $po;
                $qbMap[] = [
                    'Vendor' => $this->mapVendor($field['Supplier']),
                    'Transaction Date' => $field['Required By'],
                    '',
                ];
            }
            
            $break = 'point';
        }
        
        // all the po files have been UNION'ed
        /*
        
        */
        $qbMap['Vendor'] = $field['PO Number'];
        
    }
    
    /**
     * Dynamically find the indexes for each of the wanted fields rather than
     * hard coding each index
     *
     * Find indexes for:
     * PO Number,Required By,Value,Purchase Order Notes,SKU,Ordered Qty,Received Qty,Description,Category,Supplier
     *
     * @param $rawHeaderRow
     *
     * @return array
     */
    private function poFindKeys(array $rawHeaderRow): array {
        $headerRow = "PO Number,Required By,Value,Purchase Order Notes,SKU,Ordered Qty,Received Qty,Description,Category,Supplier";
        // the field indexes we want
        $poWantedFields = explode(',', $headerRow);
        $indexes = [];
        foreach($poWantedFields as $i => $f) {
            $indexes[$f] = array_search($f, $rawHeaderRow);
        }
        
        return $indexes;
    }
    
    /**
     * Map the Allocadence supplier to the correct QuickBooks vendor
     *
     * @param string $allocSupplier
     *
     * @return string
     */
    private function mapVendor(string $allocSupplier): string {
        $qbVendors = [
            'Cathy Welsh Envelopes',
            'Ennis, Inc',
            'Kelly Paper',
            'Southland Envelope Co. Inc',
            'Spicers Paper, Inc.',
            'Veritiv',
            'Volume Press',
            'Wilmer',
        ];
        
        foreach($qbVendors as $vendor) {
            $vendor = strtolower($vendor);
            $supplier = strtolower($allocSupplier);
            $supplier = substr($supplier, 0, strpos($supplier, ' '));
            $isVendor = (strpos($vendor, $supplier) !== false);
            if($isVendor) {
                return $vendor;
            }
        }
        
        return 'not found';
    }
    
    /**
     * Map Allocadence Receiving to QuickBooks
     */
    public function qbReceivingMap() {
        $break = 'point';
    }
}