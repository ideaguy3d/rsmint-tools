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
     *
     * This function will get files that contain 'inboundexportbydate' in its' file name from the
     * downloads folder
     *
     * Then it will export the QB mapped po's to a csv relative to the index.php file
     */
    public function qbPurchaseOrderMap(): void {
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
        
        // O(4 * ~100) = O(~400)
        // OUTER LOOP - worst case = 4 "because we only have 4 facilities"
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
            
            // INNER LOOP worst case = < ~100 "depends on how many purchase orders we make in a week, probably < 100"
            //- created the QB mapped 2D array
            foreach($poArray as $po) {
                // Allocadence fields
                $_supplier = trim($po[$field['Supplier']]);
                $_requiredBy = trim($po[$field['Required By']]);
                $_poNumber = trim($po[$field['PO Number']]);
                $_category = trim($po[$field['Category']]);
                $_orderedQty = trim($po[$field['Ordered Qty']]);
                $_orderedQty = (int)$_orderedQty;
                $_sku = trim($po[$field['SKU']]);
                $_description = trim($po[$field['Description']]);
                $_warehouse = trim($po[$field['Warehouse Name']]);
                
                $value1 = $po[$field['Value']];
                $value = str_replace(',', '', $value1);
                $_value = (float)$value;
                
                $_qbDescription = "sku: $_sku, $_description, $_supplier, Category: $_category, Amount: $ {$value1}"
                . " for $_warehouse";
                
                $qbMap[] = [
                    'Vendor' => $this->qbMapVendor($_supplier),
                    'Transaction Date' => $_requiredBy,
                    'PO Number' => $_poNumber,
                    'Item' => ($_category === 'E' ? 'Envelopes' : 'Paper'),
                    'Quantity' => number_format($_orderedQty),
                    'Description' => $_qbDescription,
                    'Rate' => (round($_value /$_orderedQty, 7)),
                ];
            }
            
        } // end of the main loop
        
        CsvParseModel::export2csv($qbMap, './', 'qb_mapped_po');
        
    } // END OF: qbPurchaseOrderMap()
    
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
        $headerRow = "PO Number,Required By,Value,Warehouse Name,SKU,Ordered Qty,Received Qty,Description,Category,Supplier";
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