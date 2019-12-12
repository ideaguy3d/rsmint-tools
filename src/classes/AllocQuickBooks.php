<?php
declare(strict_types=1);


namespace Redstone\Tools;

use stdClass;

/**
 * This way this works is by downloading the POs from Allocadence, then this class will
 * scan the downloads folder load all the files with 'inboundexportbydate' into memory
 *
 * Class AllocQuickBooks as of 11-27-2019 this class is SUPER HARDCODED and not meant to be
 * used by anyone other than me.
 *
 * @package Redstone\Tools
 */
class AllocQuickBooks
{
    private $downloadsFolder;
    
    /**
     * The raw fields for received items. PHP adds recs to this array IF the `Received Qty` is greater than 0
     * @var array
     */
    private $receivedItems;
    
    /**
     * The downloaded CSVs from Allocadence Purchase Orders I select the date range & tick 'Show Received POs'
     * @var array
     */
    private $allocPoExportFiles;
    
    /**
     * All the purchase orders combined into 1 large array (increases computer memory)
     * @var array
     */
    private $poCombined = [];
    
    /**
     * This is the Alloc mapped purchase orders for QB
     * @var array (2D array, in.ar[as.ar])
     */
    private $qbPurchaseOrderMap;
    
    /**
     * This as.ar is the field index for each of the fields from the raw file
     * @var array (assoc.)
     */
    private $field;
    
    /**
     * The raw field titles from the downloaded csv file from Allocadence
     * I used var_export() while in debug mode to get them.
     * @var array
     */
    private $rawHeaderRow;
    
    /**
     * This will be an enum for important fields from the raw file
     * @var stdClass
     */
    private $fieldTitles;
    
    public $itemReceipt;
    
    public function __construct() {
        $localDownloads = 'C:\Users\julius\Downloads';
        $proDownloads = 'C:\Users\RSMADMIN\Downloads';
        $isLocal = AppGlobals::isLocalHost();
        
        // var_export() of raw header row while debugging just to have as reference
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
            public $category = 'Category';
        };
        
        if($isLocal) {
            $this->downloadsFolder = $localDownloads;
        }
        else {
            $this->downloadsFolder = $proDownloads;
        }
        
        $poFileName = 'inboundexportbydate';
        $downloadedFiles = scandir($this->downloadsFolder);
        
        // each po file downloaded from Allocadence
        $poFilesArray = [];
        
        // get each downloaded inboundexportbydate file from Allocadence
        foreach($downloadedFiles as $file) {
            $isPoFile = (strpos($file, $poFileName) !== false);
            if($isPoFile) {
                $poFilesArray [] = "$file";
            }
        }
        
        $this->allocPoExportFiles = $poFilesArray;
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
        $field = null;
        $c = 0;
        $t = $this->fieldTitles;
        // qb maps
        $qbHeaderRowStr = "Vendor,Transaction Date,PO Number,Item,Quantity,Description,Rate";
        $qbHeaderRow = explode(",", $qbHeaderRowStr);
        $qbPurchaseOrderMap = [$qbHeaderRow];
        
        // O(4 * ~100) = O(~400)
        // OUTER LOOP - worst case = 4 "because we only have 4 facilities", but really this is going to loop over each
        // file that contains "inboundexportbydate" in the downloads folder and convert each downloaded file to an array
        // and UNION them
        foreach($this->allocPoExportFiles as $poFile) {
            $poArray = CsvParseModel::specificCsv2array($this->downloadsFolder, $poFile);
            
            // get header row real quick
            if($c === 0) {
                // add the qb mapped vendor to
                $poArray[0] [] = 'qb_vendor';
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
                $_poNum = trim($po[$field['PO Number']]);
                $_category = trim($po[$field['Category']]);
                $_orderedQty = trim($po[$field['Ordered Qty']]);
                $_orderedQty = (int)$_orderedQty;
                $_sku = trim($po[$field['SKU']]);
                $_description = trim($po[$field['Description']]);
                $_warehouse = trim($po[$field['Warehouse Name']]);
                $_received = (int)trim($po[$field[$t->receivedQty]]);
                
                if($_received > 0) {
                    $this->receivedItems [] = $po;
                }
                
                $value1 = $po[$field['Value']];
                $value = str_replace(',', '', $value1);
                $_value = (float)$value;
                
                $_qbDescription = "sku: $_sku, $_description, $_supplier, Category: $_category, "
                    . "Amount: $ {$value1} for $_warehouse";
                
                $qbVendor = $this->qbMapVendor($_supplier);
                
                $qbPurchaseOrderMap [] = [
                    'Vendor' => $qbVendor,
                    'Transaction Date' => $_requiredBy,
                    'PO Number' => $_poNum,
                    'Item' => ($_category === 'E' ? 'Envelopes' : 'Paper'),
                    'Quantity' => number_format($_orderedQty),
                    'Description' => $_qbDescription,
                    'Rate' => $_orderedQty !== 0 ? (round($_value / $_orderedQty, 7)) : 0,
                ];
                
                $po [] = $qbVendor;
                $this->poCombined [] = $po;
                
            } // end of the OUTER-LOOP
            
        } // end of the main loop
        
        $this->qbPurchaseOrderMap = $qbPurchaseOrderMap;
        
        CsvParseModel::export2csv($qbPurchaseOrderMap, './', 'qb_mapped_po');
        
    } // END OF: qbPurchaseOrderMap()
    
    /**
     * This function will operate on the Alloc Mapped QB orders and group them,
     *
     * DEPENDS ON: class field qbPurchaseOrdersMap in order for this to work the
     *      function qbPurchaseOrderMap has to be ran
     *
     * @return array = the
     */
    private function groupByPoNumber(): array {
        $grpByPo = [];
        $f = $this->field;
        $t = $this->fieldTitles;
        $purchaseOrders = $this->poCombined;
        array_shift($purchaseOrders);
        
        foreach($purchaseOrders as $item) {
            $_poNum = $item[$f[$t->poNum]];
            $grpByPo[$_poNum] [] = $item;
        }
        
        return $grpByPo;
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
        $items = [];
        $qbItemReceiptStr = "Vendor,Transaction Date,RefNumber,Item,Description	Qty	Cost,Amount	PO No.";
        $qbItemReceiptHeaderRow = explode(",", $qbItemReceiptStr);
        $qbItemReceiptMap = ['header_row' => $qbItemReceiptHeaderRow];
        
        $f = $this->field;
        $t = $this->fieldTitles;
        
        $groupByPo = $this->groupByPoNumber();
        
        // po A-00155 = E10WH-FULLW, E9WH-1W, E10BK-1W-FC-BOX, E10WH-HW-FC-BOX
        foreach($this->receivedItems as $receipt) {
            $_received = $receipt[$f[$t->receivedQty]];
            $_poNum = $receipt[$f[$t->poNum]];
            
            // W/the current data set there will only be 26 joins because only 26 items have
            // been received according to Allocadence (12-9-19@8:19pm)
            $joinOnPoGroup = $groupByPo[$_poNum] ?? null;
            if($joinOnPoGroup) {
                // each PO can only have 2 types of items E or P
                $poPaper = [
                    'Description' => '',
                    'Amount' => 0
                ];
                $poEnvelopes = [
                    'Description' => '',
                    'Amount' => 0
                ];
                
                // each $poGroup is the raw rec exported from Alloc with the qb_vendor field appended
                //... now what? These are the received items with all the data Alloc gives
                // $receipt is the the received item / raw record whose "qty received > 0"
                foreach($joinOnPoGroup as $i => $poGroup) {
                    $qbVendor = $joinOnPoGroup[0][$f['qb_vendor']];
                    $items[$_poNum] = ['Vendor' => $qbVendor];
                    $_receivedQty = (int)$poGroup[$f[$t->receivedQty]];
                    $_itemType = $poGroup[$f[$t->category]];
                    $_sku = $poGroup[0];
                    if($_itemType === 'E') {
                        $poEnvelopes['Description'] .= " | $_sku";
                        $poEnvelopes['Amount'] += $_receivedQty;
                    }
                }
                $items[$_poNum]['Amount'] += $_received;
            }
        }
    }
}