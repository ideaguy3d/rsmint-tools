<?php
declare(strict_types=1);


namespace Redstone\Tools;

use stdClass;

/**
 * Class AllocQuickBooks as of 11-27-2019 this class is SUPER HARDCODED and not meant to be
 * used by anyone other than me.
 *
 * @package Redstone\Tools
 */
class AllocQuickBooks
{
    private $downloadsFolder;
    private $receivedItems;
    private $qbPurchaseOrderMap;
    private $field;
    private $rawHeaderRow;
    /**
     * This will be an enum for important fields from the raw file
     *
     * @var stdClass
     */
    private $fieldTitles;
    
    public function __construct() {
        $localDownloads = 'C:\Users\julius\Downloads';
        $proDownloads = 'C:\Users\RSMADMIN\Downloads';
        $isLocal = AppGlobals::isLocalHost();
        $this->rawHeaderRow = [
            0 => 'PO Number',
            1 => 'Required By',
            2 => 'Ship Method',
            3 => 'Ordered By',
            4 => 'Order Prepared',
            5 => 'Value',
            6 => 'Warehouse Name',
            7 => 'Project Number',
            8 => 'Purchase Order Terms',
            9 => 'Purchase Order Notes',
            10 => 'SKU',
            11 => 'Ordered Qty',
            12 => 'Received Qty',
            13 => 'UOM',
            14 => 'Unit Cost',
            15 => 'Sales Price',
            16 => 'Description',
            17 => 'UPC / EAN',
            18 => 'Category',
            19 => 'Weight',
            20 => 'Default Econ Order',
            21 => 'Default Lead Time (Days)',
            22 => 'Recommended Retail Price',
            23 => 'Manufacturer Website',
            24 => 'Serializable',
            25 => 'Non-Inventory',
            26 => 'Perishable',
            27 => 'Track Lot',
            28 => 'Size',
            29 => 'Color',
            30 => 'Window?',
            31 => 'Special',
            32 => 'Builds',
            33 => 'Reserved',
            34 => 'Supplier',
            35 => 'Supplier Address 1',
            36 => 'Supplier Address 1',
            37 => 'Supplier City',
            38 => 'Supplier State',
            39 => 'Supplier Zip',
            40 => 'Supplier Country',
            41 => 'Supplier Main Contact',
            42 => 'Supplier Phone',
            43 => 'Supplier Email',
            44 => 'Supplier Alternative Contact',
            45 => 'Supplier Phone',
            46 => 'Supplier Email',
            47 => 'Supplier Office Phone',
            48 => 'Supplier Office Fax',
            49 => 'Supplier Website',
            50 => 'Supplier Account Number',
            51 => 'Supplier Terms',
        ];
        
        $this->fieldTitles = new class() {
            public $receivedQty = 'Received Qty';
            public $poNum = 'PO Number';
        };
        
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
        $qbPurchaseOrderMap = [$qbHeaderRow];
        
        // get each downloaded inboundexportbydate file from Allocadence
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
                $this->field = $field;
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
                $_received = (int)trim($po[$field['Received Qty']]);
                
                if($_received > 0) {
                    $this->receivedItems [] = $po;
                }
                
                $value1 = $po[$field['Value']];
                $value = str_replace(',', '', $value1);
                $_value = (float)$value;
                
                $_qbDescription = "sku: $_sku, $_description, $_supplier, Category: $_category, Amount: $ {$value1}"
                    . " for $_warehouse";
                
                $qbPurchaseOrderMap [] = [
                    'Vendor' => $this->qbMapVendor($_supplier),
                    'Transaction Date' => $_requiredBy,
                    'PO Number' => $_poNumber,
                    'Item' => ($_category === 'E' ? 'Envelopes' : 'Paper'),
                    'Quantity' => number_format($_orderedQty),
                    'Description' => $_qbDescription,
                    'Rate' => $_orderedQty !== 0 ? (round($_value / $_orderedQty, 7)) : 0,
                ];
            }
            
        } // end of the main loop
        
        $this->qbPurchaseOrderMap = $qbPurchaseOrderMap;
        
        CsvParseModel::export2csv($qbPurchaseOrderMap, './', 'qb_mapped_po');
        
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
        $headerRow = "PO Number,Required By,Value,Warehouse Name,SKU,Ordered Qty,Received Qty,Description,Category,Supplier,";
        // the field indexes we want
        $poWantedFields = explode(',', $headerRow);
        $indexes = [];
        $findAllFieldIndexes = true;
        
        if($findAllFieldIndexes) {
            // get the field index for all the fields
            foreach($rawHeaderRow as $field) {
                $indexes[$field] = array_search($field, $rawHeaderRow);
            }
        }
        else {
            // just get indexes for the wanted fields
            foreach($poWantedFields as $f) {
                $indexes[$f] = array_search($f, $rawHeaderRow);
            }
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
     * Map Allocadence Receiving to QuickBooks, the function will mutate the
     * class field $receivedItems
     */
    public function qbReceivingMap(): void {
        $break = 'point';
        $items = [];
        $qbItemReceiptStr = "Vendor,Transaction Date,RefNumber,Item,Description	Qty	Cost,Amount	PO No.";
        $qbItemReceiptHeaderRow = explode(",", $qbItemReceiptStr);
        $qbItemReceiptMap = ['header_row' => $qbItemReceiptHeaderRow];
        
        $f = $this->field;
        $t = $this->fieldTitles;
        
        foreach($this->receivedItems as $receipt) {
            $_receivedQty = $receipt[$f[$t->receivedQty]];
            $_poNum = $receipt[$f[$t->poNum]];
            
            if(isset($items[$_poNum])) {
                $items[$_poNum]['Amount'] += $_receivedQty; 
            }
            else {
                $items[$_poNum] = [
                    'Vendor'
                ];
            }
        }
    }
}