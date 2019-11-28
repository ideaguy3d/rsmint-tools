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
                $field = $this->poFindKeys($poArray[0]);
                $c++;
            }
            
            // get rid of header row real quick
            array_shift($poArray);
            
            // created the QB mapped 2D array
            foreach($poArray as $po) {
                // Allocadence fields
                $_supplier = $po[$field['Supplier']];
                $_requiredBy = $po[$field['Required By']];
                $_poNumber = $po[$field['PO Number']];
                $_category = $po[$field['Category']];
                $_orderedQty = (int)$po[$field['Ordered Qty']];
                $_sku = $po[$field['SKU']];
                $_description = $po[$field['Description']];
                $value = $po[$field['Value']];
                $value = str_replace(',', '', $value);
                $_value = (float)$value;
                
                $qbMap[] = [
                    'Vendor' => $this->qbMapVendor($_supplier),
                    'Transaction Date' => $_requiredBy,
                    'PO Number' => $_poNumber,
                    'Item' => ($_category === 'E' ? 'Envelopes' : 'Paper'),
                    'Quantity' => $_orderedQty,
                    'Description' => ('sku: ' . $_sku . ', ' . $_description),
                    'Rate' => (round($_value /$_orderedQty, 3)),
                ];
            }
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
        foreach($poWantedFields as $f) {
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
    private function qbMapVendor(string $allocSupplier): string {
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
    
        $supplier = strtolower($allocSupplier);
        $supplier = substr($supplier, 0, strpos($supplier, ' '));
        
        foreach($qbVendors as $vendor) {
            $vendorLower = strtolower($vendor);
            $isVendor = (strpos($vendorLower, $supplier) !== false);
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