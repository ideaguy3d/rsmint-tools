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
    /**
     * The windows downloads folder path
     * @var string
     */
    private $downloadsFolder;
    
    /**
     * The raw fields for received items. PHP adds recs to this array IF the `Received Qty` is greater than 0
     * This uses the PO export CSV
     * @var array
     */
    private $receivedItems;
    
    /**
     * The downloaded CSVs from Allocadence Purchase Orders. I select the date range & tick 'Show Received POs'
     * I have to click download while in West Sacramento, Denver, and Atlanta mode
     * @var array
     */
    private $allocPoExportFiles;
    
    /**
     * The CSV downloaded from Allocadence Reports > Inventory Reports > Received Inventory
     * I have to click download while in West Sacramento, Denver, and Atlanta mode
     * @var array
     */
    private $allocIrExportFiles;
    
    /**
     * All the purchase orders combined into 1 large array (increases computer memory)
     * @var array
     */
    private $poCombined = [];
    
    /**
     * All of the received items combined into 1 large array
     * @var array
     */
    private $irCombined = [];
    
    /**
     * This is the Alloc mapped purchase orders for QB
     * @var array (2D array, in.ar[as.ar])
     */
    private $qbPurchaseOrderMap; // <3
    private $qbItemReceiptMap;
    
    /**
     * This as.ar is the field index for each of the fields from the raw file
     * @var array (assoc.)
     */
    private $poField;
    private $irField;
    
    /**
     * The raw field titles from the downloaded PO export CSV file from Allocadence
     *  I used var_export() while in debug mode to get them.
     * @var array
     */
    private $poRawHeader;
    
    /**
     * The raw header row from the received items just to have as reference using the
     *  var_export() while in debug mode.
     * @var array
     */
    private $irRawHeader;
    
    /**
     * ENUM for important fields from the raw PO CSV
     * @var stdClass
     */
    private $poFieldTitles;
    
    /**
     * ENUM for important fields from raw IR csv
     * @var stdClass
     */
    private $irFieldTitles;
    
    /**
     * The QuickBooks Item Receipt fields that Allocadence Received Items
     * need to be mapped to
     *
     * @var array
     */
    public $itemReceiptFields = [
        // [Name]
        'Vendor' => '',
        // [Received Data]
        'Transaction Date' => '',
        // [Receipt]
        'RefNumber' => '',
        // [SKU]
        'Item' => '',
        // [Description]
        'Description' => '',
        // [Quantity]
        'Qty' => '',
        // [Unit Cost]
        'Cost' => '',
        // [Quantity] * [Unit Cost]
        'Amount' => 0,
        // [PO # / Receipt #]
        'PO No.' => '',
    ];
    
    /**
     * AllocQuickBooks constructor.
     */
    public function __construct() {
        $localDownloads = 'C:\Users\julius\Downloads';
        $proDownloads = 'C:\Users\RSMADMIN\Downloads';
        $isLocal = AppGlobals::isLocalHost();
        
        // var_export() of PO export raw header row while debugging just to have as reference
        $this->poRawHeader = [
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
        
        $this->irRawHeader = [];
        
        // ULTRA important field names cached into an anonymous class for better code completion
        // this is essentially an ENUM
        $this->poFieldTitles = new class() {
            public $receivedQty = 'Received Qty';
            public $poNum = 'PO Number';
            public $category = 'Category';
        };
        
        // the fields are spelled are they are in the CSV file
        $this->irFieldTitles = new class() {
            public $receipt = 'Receipt';
            public $sku = 'SKU';
            public $quantity = 'Quantity';
            public $unitCost = 'Unit Cost';
            public $receivedDate = 'Received Date';
            // PO# / Receipt#
            public $poNum = 'PO# / Receipt#';
            public $name = 'Name'; // vendor
        };
        
        if($isLocal) {
            $this->downloadsFolder = $localDownloads;
        }
        else {
            $this->downloadsFolder = $proDownloads;
        }
        
        $poFileName = 'inboundexportbydate';
        $irFileName = 'inventoryreceived';
        
        // scan all the files in the windows download folder
        $downloadedFiles = scandir($this->downloadsFolder);
        
        // each po & ir CSV downloaded from Allocadence
        $poFilesArray = [];
        $irFilesArray = [];
        
        // get each downloaded inboundexportbydate & inventoryreceived file from Allocadence
        foreach($downloadedFiles as $file) {
            $isPoFile = (strpos($file, $poFileName) !== false);
            $isIrFile = (strpos($file, $irFileName) !== false);
            
            if($isPoFile) {
                $poFilesArray [] = "$file";
            }
            
            if($isIrFile) {
                $irFilesArray [] = "$file";
            }
        }
        
        $this->allocPoExportFiles = $poFilesArray;
        $this->allocIrExportFiles = $irFilesArray;
        
    } // END OF: __construct()
    
    /**
     * Map Allocadence Purchase Orders to QuickBooks.
     *
     * This function will get files that contain 'inboundexportbydate' in its' file name from the
     * downloads folder
     *
     * Then it will export the QB mapped po's to a csv relative to the index.php file
     */
    public function qbPurchaseOrderMap(): void {
        $f = null; // field index's from raw file
        $c = 0;
        $t = $this->poFieldTitles;
        // qb maps
        $qbHeaderRowStr = "Vendor,Transaction Date,PO Number,Item,Quantity,Description,Rate";
        $qbHeaderRow = explode(",", $qbHeaderRowStr);
        $qbPurchaseOrderMap = [$qbHeaderRow];
        
        // O(4 * ~100) = O(~400) "4 CSVs with roughly a worst case of 100 recs each"
        // OUTER LOOP - worst case = 4 "because we only have 4 facilities", but really this is going to loop over each
        // file that contains "inboundexportbydate" in the downloads folder and convert each downloaded file to an array
        // and UNION them
        foreach($this->allocPoExportFiles as $poFile) {
            $poArray = CsvParseModel::specificCsv2array($this->downloadsFolder, $poFile);
            
            // get header row real quick
            if($c === 0) {
                // add the qb mapped vendor to the output QB mapped array
                $poArray[0] [] = 'qb_vendor';
                $f = $this->indexKeys($poArray[0]);
                $this->poField = $f;
                $c++;
            }
            
            // get rid of header row real quick
            array_shift($poArray);
            
            // INNER LOOP worst case = < ~100 "depends on how many purchase orders we make in a week, probably < 50"
            //- created the QB mapped 2D array
            foreach($poArray as $po) {
                // Allocadence fields
                $_supplier = trim($po[$f['Supplier']]);
                $_requiredBy = trim($po[$f['Required By']]);
                $_poNum = trim($po[$f['PO Number']]);
                $_category = trim($po[$f['Category']]);
                $_orderedQty = trim($po[$f['Ordered Qty']]);
                $_orderedQty = (int)$_orderedQty;
                $_sku = trim($po[$f['SKU']]);
                $_description = trim($po[$f['Description']]);
                $_warehouse = trim($po[$f['Warehouse Name']]);
                $_received = (int)trim($po[$f[$t->receivedQty]]);
                
                // old way the "received items" were created, this is using the PO export CSV
                if($_received > 0) {
                    $this->receivedItems [] = $po;
                }
                
                $value1 = $po[$f['Value']];
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
                
            } // end of the inner loop
            
        } // end of the main loop
        
        $this->qbPurchaseOrderMap = $qbPurchaseOrderMap;
        
        CsvParseModel::export2csv($qbPurchaseOrderMap, './', 'qb_mapped_po');
        
    } // END OF: qbPurchaseOrderMap()
    
    /**
     * Map Allocadence Received Items to QuickBooks.
     *
     * This functions scans data from the PO export CSV, not the received inventory CSV
     * The function will mutate the class field $receivedItems.
     */
    public function qbReceivingMap(): void {
        $items = [];
        $itemReceiptFields = $this->itemReceiptFields;
        
        $f = $this->poField;
        $t = $this->poFieldTitles;
        
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
                $poPaper = ['Description' => '', 'Amount' => 0];
                $poEnvelopes = $itemReceiptFields;
                
                // each $poGroup is the raw rec exported from Alloc with the qb_vendor field appended
                //... now what? These are the received items with all the data Alloc gives
                // $receipt is the the received item raw record whose "qty received > 0"
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
        
    } // END OF: qbReceivingMap()
    
    /**
     * Map Allocadence Received Items to QuickBooks.
     *
     * This function will use the received inventory CSV
     */
    public function qbItemReceiptMap(): void {
        $f = null; // field index's from raw file
        $c = 0;
        $t = $this->irFieldTitles;
        $qbItemReceiptStr = "Vendor,Transaction Date,RefNumber,Item,Description	Qty	Cost,Amount	PO No.";
        $qbItemReceiptHeaderRow = explode(",", $qbItemReceiptStr);
        $qbItemReceiptMap = ['header_row' => $qbItemReceiptHeaderRow];
        $itemReceiptFields = $this->itemReceiptFields;
        
        // enum for QB Item Receipts fields
        $qb = new class() {
            public $vendor = 'Vendor';
            public $transactionDate = 'Transaction Date';
            public $refNumber = 'RefNumber';
            public $item = 'Item';
            public $description = 'Description';
            public $qty = 'Qty';
            public $cost = 'Cost';
            public $amount = 'Amount';
            public $poNum = 'PO No.';
        };
        
        // OUTER LOOP
        // 1st create the irCombined array, create the indexed keys for the received items array
        foreach($this->allocIrExportFiles as $irFile) {
            $irArray = CsvParseModel::specificCsv2array($this->downloadsFolder, $irFile);
            
            if($c === 0) {
                $this->irCombined [] = $irArray[0];
                $f = $this->indexKeys($irArray[0]);
                // not sure adding as class field is needed or useful
                $this->irField = $f;
                $c++;
            }
            
            // get rid of header row, it's already been added
            array_shift($irArray);
            
            // make irCombined a 2D array
            foreach($irArray as $item) {
                $this->irCombined [] = $item;
            }
            
            unset($irArray);
        }
        
        // reset counter
        $c = 0;
        $groupByPo = [];
        
        // OUTER LOOP
        // loop over all the combined received items to group by PO Number
        foreach($this->irCombined as $receivedItem) {
            // skip header row
            if($c === 0) {
                $c++;
                continue;
            }
            
            // some POs have a blank PO val
            $_poNum = trim($receivedItem[$f[$t->poNum]]);
            $groupByPo[$_poNum] [] = $receivedItem;
        }
        
        // OUTER LOOP
        // loop over each group to create an Item Receipt
        foreach($groupByPo as $po => $poGroup) {
            $skuGroup = [];
            
            // INNER LOOP 1
            // loop over each element in the po group to group by sku
            foreach($poGroup as $poNum => $receivedItem) {
                $_sku = trim($receivedItem[$f[$t->sku]]);
                $skuGroup[$_sku] [] = $receivedItem;
            }
            
            // INNER LOOP 2
            // loop over each item in the sku group
            foreach($skuGroup as $sku => $groupByItemReceipt) {
                $category = $groupByItemReceipt[0][$f['Category']];
                $itemReceipt = [
                    // DONE - vendor map
                    'Vendor' => $this->qbMapVendor($groupByItemReceipt[0][$f[$t->name]]),
                    // multiple vals possible
                    'Transaction Date' => '',
                    // multiple item receipts possible
                    'RefNumber' => '',
                    // DONE - item map
                    'Item' => $category === 'E' ? 'Envelopes' : strpos($category, 'P') !== false ? 'Paper' : 'unknown',
                    // calculated field
                    'Description' => "sku: $sku - ",
                    // calculated field "sum of all Quantities from alloc csv"
                    'Qty' => 0,
                    // multiple costs possible, BUT UNLIKELY
                    'Cost' => 0.0,
                    // calculated field, qty * cost
                    'Amount' => 0.0,
                    // DONE - simple map
                    'PO No.' => $groupByItemReceipt[0][$f[$t->poNum]],
                ];
                
                $qtyStr = '';
                
                // INNER LOOP 3
                // loop over each SKU in the Purchase Order group
                foreach($groupByItemReceipt as $key => $receivedItem) {
                    // cache relevant Allocadence fields values
                    $_receipt = trim($receivedItem[$f[$t->receipt]]);
                    $_sku = trim($receivedItem[$f[$t->sku]]);
                    $_quantity = trim($receivedItem[$f[$t->quantity]]);
                    $_quantityInt = (int)$_quantity;
                    $_unitCost = trim($receivedItem[$f[$t->unitCost]]);
                    $_unitCostFloat = (float)trim($_unitCost);
                    $_receivedDate = trim($receivedItem[$f[$t->receivedDate]]);
                    $_poNum = trim($receivedItem[$f[$t->poNum]]);
                    $_name = trim($receivedItem[$f[$t->name]]);
                    
                    // cache relevant QB item receipt values
                    //$qbTransactionDate = &$itemReceipt[$qb->transactionDate];
                    //$qbRefNum = &$itemReceipt[$qb->refNumber];
                    $qbTransactionDate = &$itemReceipt[$qb->transactionDate];
                    $qbRefNum = &$itemReceipt[$qb->refNumber];
                    $qbDescription = &$itemReceipt[$qb->description];
                    $qbQty = &$itemReceipt[$qb->qty];
                    $qbCost = &$itemReceipt[$qb->cost];
                    $qbAmount = &$itemReceipt[$qb->amount];
                    
                    // `TRANSACTION DATE`
                    // check if there are multiple values for received date
                    if(empty($qbTransactionDate)) {
                        $qbTransactionDate = $_receivedDate;
                    }
                    else {
                        $qbTransactionDate .= ", $_receivedDate";
                    }
    
                    // date
                    $datePattern = '~(\d+/\d+/\d+)~m';
                    preg_match($datePattern, $_receivedDate, $dateMatch);
                    // time
                    $timePattern = '~(\d+:\d+.+)~';
                    preg_match($timePattern, $_receivedDate, $timeMatch);
                    
                    // `REF NUMBER` & `DESCRIPTION`
                    if(empty($qbRefNum)) {
                        $qbRefNum = $_receipt;
                        $d = "Receipt $_receipt received _rr_ on {$dateMatch[0]} at {$timeMatch[0]}";
                        $qbDescription .= $d;
                    }
                    else {
                        $qbRefNum .= ", $_receipt";
                    }
                    
                    // `QUANTITY`
                    $qtyFormatted = number_format($_quantityInt);
                    $qtyStr .= "$qtyFormatted+";
                    $qbQty += $_quantityInt;
                    
                    // `COST` &  `AMOUNT`
                    if($qbCost == 0.0 || $qbCost == $_unitCost) {
                        $qbCost = $_unitCostFloat;
                        $qbAmount = ($qbQty * $qbCost);
                    }
                    else {
                        // if there are multiple costs
                        if($_unitCost !== $qbCost) {
                            $qbCost .= "ERROR, unit costs: $_unitCost, $qbCost";
                        }
                        else {
                            $qbAmount = ($qbAmount + ($_unitCostFloat * $_quantity));
                        }
                    }
                    
                } // end of inner loop
                
                if(isset($qbDescription)) {
                    $qtyStr = substr($qtyStr, 0, -1);
                    $qbDescription = str_replace('_rr_', $qtyStr, $qbDescription);
                }
                
                $itemReceipt = [
                    array_keys($itemReceipt),
                    array_values($itemReceipt)
                ];
                
                CsvParseModel::export2csv($itemReceipt, '.\_item_receipts', "item-receipt_{$po}_$sku");
            }
        }
        
        // create each item receipt
        
    } // END OF: qbReceivingMap()
    
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
        $f = $this->poField;
        $t = $this->poFieldTitles;
        $purchaseOrders = $this->poCombined;
        array_shift($purchaseOrders);
        
        foreach($purchaseOrders as $item) {
            $_poNum = $item[$f[$t->poNum]];
            $grpByPo[$_poNum] [] = $item;
        }
        
        return $grpByPo;
    }
    
    /**
     * Dynamically find the indexes rather than hard coding each index
     *
     * @param $rawHeaderRow
     *
     * @return array
     */
    private function indexKeys(array $rawHeaderRow): array {
        $indexes = [];
        
        // get the field index for all the fields
        foreach($rawHeaderRow as $field) {
            $indexes[$field] = array_search($field, $rawHeaderRow);
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
        
    } // END OF: qbMapVendor()
    
} // end of class