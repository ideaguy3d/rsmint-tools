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
 * @class AllocadenceQuickBooks
 * @package Redstone\Tools
 */
class AllocadenceQuickBooks
{
    /**
     * The windows downloads folder path
     */
    private string $inFolder_downloads;
    
    /**
     * The name of the exported PO csv, PHP attempts to give it the correct week num
     * and attempts to append each facility scanned into the name
     */
    private string $outFileName_purchaseOrders;
    
    /**
     * The downloaded CSVs from Allocadence Purchase Orders. I select the date range & tick 'Show Received POs'
     * I have to click download while in West Sacramento, Denver, and Atlanta mode
     */
    private array $inFilesList_allocPoDownloads;
    
    /**
     * The CSV downloaded from Allocadence "Reports > Inventory Reports > Received Inventory"
     * I have to click download while in West Sacramento, Denver, Atlanta, E&F, Baltimore mode
     */
    private array $inFilesList_allocItemReceiptsDownloads;
    
    /**
     * All the purchase orders combined into 1 large array (increases computer memory)
     */
    private array $combinedPo;
    
    /**
     * All of the received items combined into 1 large array
     */
    private array $combinedIr;
    
    /**
     * This is the Alloc mapped purchase orders and item receipts
     * for QB (2D array, in.ar[as.ar])
     */
    private array $qbPurchaseOrderMap;
    
    /**
     * This as.ar is the field index for each of the fields from the raw file
     */
    private array $indexPo;
    
    /**
     * The QB vendors exported from QuickBooks with an assigned ID to join on.
     */
    private array $qbVendors;
    
    /**
     * I manually scraped the suppliers to get this CSV by copying the HTML and using
     * Instant Scraper on that.
     */
    private array $allocSuppliers;
    
    /**
     * ENUM for important fields names aka titles / header row
     * from the raw PO CSV
     */
    private object $titlesPo;
    
    /**
     * ENUM for important fields names aka titles / header row
     * from raw IR csv
     */
    private object $titlesIr;
    
    /**
     * The QuickBooks Item Receipt fields that Allocadence Received Items
     * need to be mapped to
     */
    private array $irFields;
    
    /* Class Initializations */
    private string $outFolder_itemReceipts = 'csv/_item-receipts';
    private string $outFolder_poExport = 'csv/_purchase-orders';
    private string $inFolder_requiredCsv = 'csv/@required_csv';
    private string $inFileName_qbVendors = 'quickbooks-vendors.csv';
    private string $inFileName_allocSuppliers = 'allocadence-suppliers.csv';
    
    public function __construct() {
        $localDownloads = 'C:\Users\julius\Downloads';
        $proDownloads = 'C:\Users\RSMADMIN\Downloads';
        $isLocal = AppGlobals::isLocalHost();
        
        $qbVendors = CsvParseModel::specificCsv2array($this->inFolder_requiredCsv, $this->inFileName_qbVendors);
        $this->qbVendors = $this->hashArray($qbVendors);
        
        $allocSuppliers = CsvParseModel::specificCsv2array($this->inFolder_requiredCsv, $this->inFileName_allocSuppliers);
        $this->allocSuppliers = $this->hashArray($allocSuppliers);
        
        // ULTRA important field names cached into an object
        $this->titlesPo = new class() {
            public $poNum = 'PO Number';
            // E, P, PS
            public $category = 'Category';
            // vendor
            public $supplier = 'Supplier';
            public string $warehouse = 'Warehouse Name';
        };
        
        // the fields are spelled as they are in the Alloc CSV file
        $this->titlesIr = new class() {
            public $receipt = 'Receipt';
            public $sku = 'SKU';
            public $quantity = 'Quantity';
            public $unitCost = 'Unit Cost';
            public $receivedDate = 'Received Date';
            
            // PO# and Receipt#
            public $poNum = 'PO# / Receipt#';
            public $name = 'Name'; // vendor
        };
        
        if($isLocal) {
            $this->inFolder_downloads = $localDownloads;
        }
        else {
            $this->inFolder_downloads = $proDownloads;
        }
        
        $poFileName = 'inboundexportbydate';
        $irFileName = 'inventoryreceived';
        
        // scan all the files in the windows download folder
        $downloadedFiles = scandir($this->inFolder_downloads);
        
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
        
        $this->inFilesList_allocPoDownloads = $poFilesArray;
        $this->inFilesList_allocItemReceiptsDownloads = $irFilesArray;
        
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
        $facilities = '';
        
        $ff = new class () {
            public array $idx;
            public int $c = 0;
            public string $facilities = '';
        };
        
        // formatted description for sprintf() in PO loop
        $fDes = 'sku: %s, %s, %s, Category: %s, Amount: $ %s for %s';
        
        // qb maps
        $qbHeaderRowStr = "Vendor,Transaction Date,PO Number,Item,Quantity,Description,Rate";
        $qbHeaderRow = explode(",", $qbHeaderRowStr);
        $qbPurchaseOrderMap = [$qbHeaderRow];
        
        // MAIN_LOOP: loop over each file that contains "inboundexportbydate" in the downloads folder & UNION them,
        // worst case = O(4 * ~100) "4 CSVs with roughly a worst case of 100 recs each"
        foreach($this->inFilesList_allocPoDownloads as $poFile) {
            $poArray = CsvParseModel::specificCsv2array($this->inFolder_downloads, $poFile);
            
            // if there are no records, don't process it
            if(1 === count($poArray)) continue;
            
            $poHash = $this->hashArray($poArray);
            
            // do special ops while on header row real quick
            if($c === 0) {
                // add the qb mapped vendor to the output QB mapped array
                $poArray[0] [] = 'qb_vendor';
                $f = $this->indexPo = $ff->idx = $this->indexKeys($poArray[0]);
                
                // our facilities abbreviated
                $facilities .= 'SAC';
                $facilities .= ' DEN';
                $facilities .= ' ATL';
                $facilities .= ' E&F';
                $facilities .= ' BAL';
            }
    
            // reach ahead a rec to get the ware house
            $facExplode = explode(' ', $poHash[1][$this->titlesPo->warehouse]);
            if(count($facExplode) > 1) {
                $a = substr($facExplode[0], 0, 1);
                $b = substr($facExplode[1], 0, 3);
                $ff->facilities .= strtoupper(" $a.$b");
            }
            else {
                $a = substr($facExplode[0], 0, 3);
                $ff->facilities .= strtoupper(" $a");
            }
    
            $debug = 1;
            
            // get rid of header row real quick
            array_shift($poArray);
            
            // INNER_LOOP_1: worst case = < ~100 "depends on how many purchase orders we make in a week, probably < 50"
            //- created the QB mapped 2D array
            foreach($poArray as $i => $po) {
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
                
                $value1 = $po[$f['Value']];
                $value = str_replace(',', '', $value1);
                $_value = (float)$value;
                $_qbDescription = sprintf($fDes, $_sku, $_description, $_supplier, $_category, $value1, $_warehouse);
                
                $qbVendor = $this->qbMapVendor($_supplier);
                
                $qbPurchaseOrderMap [] = [
                    'Vendor' => $qbVendor,
                    'Transaction Date' => $_requiredBy,
                    'PO Number' => $_poNum,
                    'Item' => 'Purchase Order:' . ($_category === 'E' ? 'Envelopes' : 'Paper'),
                    'Quantity' => number_format($_orderedQty),
                    'Description' => $_qbDescription,
                    'Rate' => $_orderedQty !== 0 ? (round($_value / $_orderedQty, 7)) : 0,
                ];
                
                $po [] = $qbVendor;
                $this->combinedPo [] = $po;
                
            } // end of INNER_LOOP_1
            
            $c++;
            
        } // end of: MAIN_LOOP
        
        $this->qbPurchaseOrderMap = $qbPurchaseOrderMap;
        
        // h:i:s a
        $date = date('m/d/Y', time());
        try {
            $date = new \DateTime($date);
        }
        catch(\Throwable $e) {
            AppGlobals::rsLogInfo($e->getMessage());
        }
        $week = (int)$date->format("W") - 1;
        $this->outFileName_purchaseOrders = $file = "purchase orders 2020 week $week $ff->facilities";
        $path = $this->outFolder_poExport;
        CsvParseModel::export2csv($qbPurchaseOrderMap, $path, $file);
        
    } // END OF: qbPurchaseOrderMap()
    
    /**
     * Map Allocadence Received Items to QuickBooks.
     * This function will use the received inventory CSV.
     */
    public function qbItemReceiptMap(): void {
        $f = null; // field index's from raw file
        $c = 0;
        $t = $this->titlesIr;
        
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
        foreach($this->inFilesList_allocItemReceiptsDownloads as $irFile) {
            $irArray = CsvParseModel::specificCsv2array($this->inFolder_downloads, $irFile);
            
            if($c === 0) {
                $this->combinedIr [] = $irArray[0];
                $f = $this->indexKeys($irArray[0]);
                
                // not sure adding as class field is needed or useful
                $this->irField = $f;
                $c++;
            }
            
            // get rid of header row, it's already been added
            array_shift($irArray);
            
            // make irCombined a 2D array
            foreach($irArray as $item) {
                $this->combinedIr [] = $item;
            }
            
            unset($irArray);
        }
        
        // reset counter
        $c = 0;
        $groupByPo = [];
        
        // OUTER LOOP
        // loop over all the combined received items to group by PO Number
        foreach($this->combinedIr as $receivedItem) {
            // skip header row
            if($c === 0) {
                $c++;
                continue;
            }
            
            // some POs have a blank PO value
            $_poNum = trim($receivedItem[$f[$t->poNum]]);
            $groupByPo[$_poNum] [] = $receivedItem;
        }
        
        function map_item($category, $sku) {
            if($category === 'E') return 'Envelopes';
            else if(strpos($category, 'P') !== false) return 'Paper';
            else if(strpos(strtolower($sku), 'freight') !== false) {
                return 'Freight In (Non Mail)';
            }
            else {
                return 'unknown';
            }
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
                $item = $groupByItemReceipt[0][$f['SKU']];
                $itemReceipt = [
                    // DONE - vendor map
                    'Vendor' => $this->qbMapVendor($groupByItemReceipt[0][$f[$t->name]]),
                    // multiple values possible
                    'Transaction Date' => '',
                    // multiple item receipts possible
                    'RefNumber' => '',
                    // DONE - item map
                    'Item' => map_item($category, $item),
                    // calculated field
                    'Description' => "sku: $sku ",
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
                $qtyAr = [];
                
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
                    $datePattern = '~(\d+/\d+/\d+)~';
                    preg_match($datePattern, $_receivedDate, $dateMatch);
                    // time
                    $timePattern = '~(\d+:\d+.+)~';
                    preg_match($timePattern, $_receivedDate, $timeMatch);
                    
                    // format the quantity
                    $qtyFormatted = number_format($_quantityInt);
                    $qtyStr = "$qtyFormatted+";
                    
                    // `REF NUMBER` & `DESCRIPTION`
                    if(empty($qbRefNum)) {
                        $qbRefNum = $_receipt;
                        $qtyAr[] = $qtyStr;
                        $d = "Receipt $_receipt received _rr_ on {$dateMatch[0]} at {$timeMatch[0]}";
                        $qbDescription .= $d;
                    }
                    else {
                        $qtyAr[] = $qtyStr;
                        $qbRefNum .= ", $_receipt";
                        $d = " & Receipt $_receipt received _rr_ on {$dateMatch[0]} at {$timeMatch[0]}";
                        $qbDescription .= $d;
                    }
                    
                    // `QUANTITY`
                    $qbQty += $_quantityInt;
                    
                    // `COST` &  `AMOUNT`
                    if($qbCost == 0.0 || $qbCost == $_unitCost) {
                        $qbCost = $_unitCostFloat;
                        $qbAmount = ($qbQty * $qbCost);
                    }
                    else {
                        // if there are multiple costs
                        if($_unitCost !== $qbCost) {
                            $qbCost .= "ERROR - multiple unit costs: $_unitCost, $qbCost.";
                        }
                        else {
                            $qbAmount = ($qbAmount + ($_unitCostFloat * $_quantity));
                        }
                    }
                } // end of inner loop
                
                // add the formatted quantity string
                if(isset($qbDescription)) {
                    if(count($qtyAr) === 1) {
                        $qtyStr = substr($qtyStr, 0, -1);
                        $qbDescription = str_replace('_rr_', $qtyStr, $qbDescription);
                    }
                    else {
                        foreach($qtyAr as $qty) {
                            $qty = substr($qty, 0, -1);
                            $pos = strpos($qbDescription, '_rr_');
                            $qbDescription = substr_replace($qbDescription, $qty, $pos, 4);
                        }
                    }
                }
                
                $itemReceipt = [array_keys($itemReceipt), array_values($itemReceipt)];
                
                CsvParseModel::export2csv(
                    $itemReceipt, $this->outFolder_itemReceipts, "item-receipt_{$po}_$sku"
                );
            }
            
        } // end of outer loop
        
    } // END OF: qbItemReceiptsMap()
    
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
        $f = $this->indexPo;
        $t = $this->titlesPo;
        $purchaseOrders = $this->combinedPo;
        array_shift($purchaseOrders);
        
        foreach($purchaseOrders as $item) {
            $_poNum = $item[$f[$t->poNum]];
            $grpByPo[$_poNum] [] = $item;
        }
        
        return $grpByPo;
    }
    
    /**
     * Use the values in the header row to create a hashed array
     * So basically convert an indexed array to an associative array
     *
     * @param array $indexedArrayTable - an 2D array like [['po', 'qty'],[123, 1000]]
     *
     * @return array - return an array like [['po' => 'po', 'qty'=>'qty], ['po'=>123, 'qty'=>1000]]
     */
    private function hashArray(array $indexedArrayTable): array {
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
    private function qbMapVendor(string $allocSupplier): ?string {
        // [["Ennis, Inc", 0], ["Wilmer", 1]... etc.]
        $qbVendors = $this->qbVendors;
        $matchedAllocSupplier = null;
        
        // match the current $allocSupplier to a supplier in the Alloc Supplier CSV
        foreach($this->allocSuppliers as $supplierRec) {
            if(trim($allocSupplier) == trim($supplierRec['vendor'])) {
                $matchedAllocSupplier = $supplierRec;
                break;
            }
        }
        
        // match the [vendor_id] from the allocadence suppliers csv to the quickbooks vendors csv
        foreach($qbVendors as $hash => $vendorRec) {
            if(!isset($vendorRec)) {
                $rsError = '__ERROR: Unknown vendor "' . var_export($vendorRec, true) . '"';
                AppGlobals::rsLogInfo($rsError);
                return $rsError;
            }
            else if(!isset($matchedAllocSupplier)) {
                $rsError = '__ERROR: Unknown supplier "' . $allocSupplier . '" - ';
                $rsError .= var_export($matchedAllocSupplier, true);
                AppGlobals::rsLogInfo($rsError);
                return $rsError;
            }
            
            // _HARD CODED: 0 = vendor name, 1 = vendor id
            if($vendorRec['vendor_id'] === $matchedAllocSupplier['vendor_id']) {
                return $vendorRec['vendor'];
            }
            
        } // end of loop
        
        return "Vendor '$allocSupplier' Not Found";
        
    } // END OF: qbMapVendor()
    
} // end of class