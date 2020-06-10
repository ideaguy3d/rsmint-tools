<?php
/**
 * Created by PhpStorm.
 * User: julius hernandez alvarado
 * Date: 6/1/2020
 * Time: 6:45 PM
 */

namespace Redstone\Tools;


class AllocadenceInvUsuageCosts extends Allocadence
{
    private object $titlesOrderDetails;
    
    private object $titlesInvReceived;
    
    private string $inFileName_sacCosts = 'cost_inv_receive_SAC_2019oct_2020may.csv';
    
    private string $inFileName_atlCosts = 'cost_inv_receive_ATL_2019oct_2020may.csv';
    
    private string $inFileName_balCosts = 'cost_inv_receive_BAL_2019oct_2020may.csv';
    
    private string $inFileName_denCosts = 'cost_inv_receive_DEN_2019oct_2020may.csv';
    
    private string $inFileName_enfCosts = 'cost_inv_receive_EnF_2019oct_2020may.csv';
    
    private array $i_atlCosts;
    
    private array $i_balCosts;
    
    private array $i_denCosts;
    
    private array $i_enfCosts;
    
    private array $i_sacCosts;
    
    private array $orderDetails;
    
    private array $noSkuCost;
    
    private array $noSkuCostSlim;
    
    private array $facHashTable;
    
    private string $outFolder_invUsage = 'csv/_inv-usage-cost';
    
    public function __construct() {
        parent::__construct();
        
        $this->titlesOrderDetails = new class() {
            public string $sku = 'SKU';
            public string $qty = 'Picked Qty';
            public string $fac = 'Ship From';
            public string $cat = 'Category';
            public string $completeOn = 'Completed On';
            public string $placeOn = 'Placed On';
        };
        
        $this->titlesInvReceived = new class() {
            public string $sku = 'SKU';
            public string $cost = 'Unit Cost';
            public string $date = 'Received Date';
        };
        
        $this->i_atlCosts = $this->hashArray(
            CsvParseModel::specificCsv2array($this->inFolder_requiredCsv, $this->inFileName_atlCosts)
        );
        
        $this->i_balCosts = $this->hashArray(
            CsvParseModel::specificCsv2array($this->inFolder_requiredCsv, $this->inFileName_balCosts)
        );
        
        $this->i_denCosts = $this->hashArray(
            CsvParseModel::specificCsv2array($this->inFolder_requiredCsv, $this->inFileName_denCosts)
        );
        
        $this->i_enfCosts = $this->hashArray(
            CsvParseModel::specificCsv2array($this->inFolder_requiredCsv, $this->inFileName_enfCosts)
        );
        
        $this->i_sacCosts = $this->hashArray(
            CsvParseModel::specificCsv2array($this->inFolder_requiredCsv, $this->inFileName_sacCosts)
        );
        
        // there will only be 1 'orderdetails.csv" file
        $ordersFilePath = 'orderdetails';
        foreach($this->downloadedFiles as $file) {
            if(strpos($file, $ordersFilePath) !== false) {
                $ordersFilePath = $file;
            }
        }
        
        $this->orderDetails = $this->hashArray(
            CsvParseModel::specificCsv2array($this->inFolder_downloads, $ordersFilePath)
        );
        
        $this->partitionByMostRecentCost();
    }
    
    /**
     * Create the CSV files for each facility
     */
    public function calcInUsageCosts(): void {
        $ff = new class() {
            // k = key
            public string $kAtl = 'Atlanta';
            public string $kBal = 'Baltimore';
            public string $kDen = 'Denver';
            public string $kEnf = 'E&F';
            public string $kSac = 'West Sacramento';
            // the RS Inv Costs field names
            public string $rsCost = 'total_cost';
            public string $rsOrder = 'total_orders';
            public string $rsQty = 'total_quantity';
            public string $rsFac = 'facility';
            // Costs' by SKU & Category
            public string $rsSku = 'SKU';
            public string $rsCat = 'Category';
            // SKU COST ARRAY
            public array $facSkuCost;
            // CATEGORY COST ARRAY
            public array $facCategoryCost;
            // a list of valid facilities
            public array $facList;
            
            public function __construct() {
                $this->facList = [
                    $this->kAtl, $this->kBal, $this->kDen, $this->kEnf, $this->kSac,
                    'atl', 'bal', 'den', 'enf', 'sac',
                ];
            }
        };
        
        $facRemap = function(string $fac) {
            if(stripos($fac, 'atl') !== false) {
                return 'atl';
            }
            else if(stripos($fac, 'bal') !== false) {
                return 'bal';
            }
            else if(stripos($fac, 'den') !== false) {
                return 'den';
            }
            else if(stripos($fac, 'e&f') !== false) {
                return 'enf';
            }
            else if(stripos($fac, 'sac') !== false) {
                return 'sac';
            }
            else {
                $break = 1;
                return 'unknown';
            }
        };
        
        // OUTER_LOOP_1, calculate the SKU costs
        foreach($this->orderDetails as $i => $order) {
            if(0 === $i) continue;
            $_fac = $facRemap($order[$this->titlesOrderDetails->fac]);
            $_qty = $order[$this->titlesOrderDetails->qty];
            $_sku = $order[$this->titlesOrderDetails->sku];
            $_category = $order[$this->titlesOrderDetails->cat];
            $_completeOn = $order[$this->titlesOrderDetails->completeOn];
            $_placeOn = $order[$this->titlesOrderDetails->placeOn];
            
            // if SKU has already been added to the no cost table, continue
            if(isset($this->noSkuCost[$_fac][$_sku])) continue;
            
            // some SKUs contain '-cli'
            if(stripos($_sku, 'cli') !== false) {
                $_sku = str_ireplace('-cli', '', $_sku);
            }
            // do NOT include freight
            if(stripos($_sku, 'inbound') !== false) {
                $this->noSkuCost[$_fac][$_sku] [] = $order;
                continue;
            }
            
            // make sure the order is from a known facility
            if(!in_array($_fac, $ff->facList)) {
                $this->noSkuCost[$_fac][$_sku] [] = $order;
                continue;
            }
            
            // 'cost' may be undefined if something went wrong
            $facHashTableSkuCost = $this->facHashTable[$_fac][$_sku]['cost'] ?? null;
            if(is_null($facHashTableSkuCost)) {
                $facHashTableSkuCost = $this->checkBotSupplierInfo($_sku, $_fac, $order);
            }
            if(is_null($facHashTableSkuCost)) {
                continue;
            }
            $c_skuCost = ($_qty * $facHashTableSkuCost);
            
            // _SUPER IMPORTANT, this builds the SKU report
            if(isset($ff->facSkuCost[$_fac][$_sku])) {
                $ff->facSkuCost[$_fac][$_sku][$ff->rsCost] += $c_skuCost;
                $ff->facSkuCost[$_fac][$_sku][$ff->rsOrder]++; // just init the first agg
                $ff->facSkuCost[$_fac][$_sku][$ff->rsQty] += $_qty;
            }
            // not set, so init values
            else {
                $ff->facSkuCost[$_fac][$_sku][$ff->rsSku] = $_sku;
                $ff->facSkuCost[$_fac][$_sku][$ff->rsCost] = $c_skuCost;
                $ff->facSkuCost[$_fac][$_sku][$ff->rsOrder] = 1; // just init the first agg
                $ff->facSkuCost[$_fac][$_sku][$ff->rsQty] = $_qty;
                $ff->facSkuCost[$_fac][$_sku][$ff->rsFac] = $_fac;
            }
            
            // _SUPER IMPORTANT, this builds the Category report
            if(isset($ff->facCategoryCost[$_fac][$_category])) {
                $ff->facCategoryCost[$_fac][$_category][$ff->rsCost] += $c_skuCost;
                $ff->facCategoryCost[$_fac][$_category][$ff->rsOrder]++;
                $ff->facCategoryCost[$_fac][$_category][$ff->rsQty] = $_category;
            }
            else {
                $ff->facCategoryCost[$_fac][$_category][$ff->rsCat] = $_category;
                $ff->facCategoryCost[$_fac][$_category][$ff->rsCost] = $c_skuCost;
                $ff->facCategoryCost[$_fac][$_category][$ff->rsOrder] = 1; // just init the first agg
                $ff->facCategoryCost[$_fac][$_category][$ff->rsQty] = $_qty;
                $ff->facCategoryCost[$_fac][$_category][$ff->rsFac] = $_fac;
            }
            
            $debug = 1;
            
        } // end of: OUTER_LOOP_1
        
        // export the category report
        foreach($ff->facCategoryCost as $fac => $records) {
            $key = array_key_first($records);
            $headerRow = array_keys($records[$key]);
            array_unshift($records, $headerRow);
            
            CsvParseModel::export2csv(
                $records, $this->outFolder_invUsage, "$fac-category-costs"
            );
        }
        
        // export the SKU report
        foreach($ff->facSkuCost as $fac => $records) {
            $key = array_key_first($records);
            $headerRow = array_keys($records[$key]);
            array_unshift($records, $headerRow);
            
            CsvParseModel::export2csv(
                $records, $this->outFolder_invUsage, "$fac-sku-costs"
            );
        }
    
        /*
                [
                    [] // header
                    [] // body
                ]
            
                [
                    [[]]
                    [[]]
                ]
            */
    
        // 70+ fields
        $ordDetailsNoCost = [];
        // 24 fields
        $invReceivedNoCost = [];
    
        $normNoCosts = function(array &$set) use (&$rec) {
            if(count($set) === 0) $set [] = array_keys($rec);
            $set [] = $rec;
        };
    
        // normalize from [[[]][[]]] to [[][]], O(5n), only 2-5 facilities
        foreach($this->noSkuCost as $fac => $records) {
            foreach($records as $sku => $rec) {
                $this->noSkuCostSlim[$sku] ??= [$sku, '???'];
                $rec = $rec[0]['all_data'] ?? $rec[0];
            
                // inv received
                if(count($rec) < 30) $normNoCosts($invReceivedNoCost);
                // ord details
                else $normNoCosts($ordDetailsNoCost);
            }
        }
    
        CsvParseModel::export2csv(
            $invReceivedNoCost, $this->outFolder_invUsage . "/no-costs", "no_cost_inventory_received"
        );
        CsvParseModel::export2csv(
            $ordDetailsNoCost, $this->outFolder_invUsage . "/no-costs", "no_cost_order_details"
        );
        CsvParseModel::export2csv(
            $this->noSkuCostSlim, $this->outFolder_invUsage . "/no-costs", 'simple_no_cost_list'
        );
        
        $debug = 1;
        
    }
    
    /**
     * Cost has not been found for an order or from the inventory received, so
     * check the scraped supplier info table
     *
     * DEPENDS ON CLASS PROP: $this->facHashTable
     *
     * @param string $sku
     * @param string $fac
     * @param array|null $order
     *
     * @return float|null
     */
    private function checkBotSupplierInfo(string $sku, string $fac, array $order = null): ?float {
        foreach($this->botSupplierInfo as $item) {
            if($item['sku'] == $sku) {
                return (float)$item['unit_cost'];
            }
        }
        
        $this->noSkuCost[$fac][$sku] = is_null($order)
            ? $this->facHashTable[$fac][$sku] : [$order];
        
        return null;
    }
    
    /**
     * Partition each of the cost by facility arrays
     */
    private function partitionByMostRecentCost() {
        $facCosts = [
            'atl' => $this->i_atlCosts,
            'bal' => $this->i_balCosts,
            'den' => $this->i_denCosts,
            'enf' => $this->i_enfCosts,
            'sac' => $this->i_sacCosts,
        ];
        
        // OUTER_LOOP_1 O(6*n)
        foreach($facCosts as $fac => $costSet) {
            $_sku = null;
            
            // inner_loop_1, create the $facHashTable
            foreach($costSet as $i => $costs) {
                if(0 === $i) continue;
                
                $_sku = $costs[$this->titlesOrderDetails->sku];
                if(stripos($_sku, 'cli') !== false) {
                    $_sku = str_ireplace('-cli', '', $_sku);
                }
                $_cost = $costs [$this->titlesInvReceived->cost];
                $_date = $costs[$this->titlesInvReceived->date];
                
                $this->facHashTable[$fac][$_sku] [] = [
                    'sku' => $_sku, 'cost' => $_cost, 'date' => $_date, 'fac' => $fac, 'all_data' => $costs,
                ];
            }
            
            // inner_loop_2 over $facHashTable, as the facHashTable is being constructed loop over
            // the just created $fac.$sku hash
            foreach($this->facHashTable[$fac] as $sku => $skuTable) {
                $dates = array_column($skuTable, 'date');
                arsort($dates);
                $datesSorted = array_values($dates);
                
                $cost = $this->recursivePartitionCost($skuTable, $datesSorted);
                
                // search the bot scraped data for a supplier cost
                if(is_null($cost)) {
                    $cost = $this->checkBotSupplierInfo($sku, $fac);
                }
                
                // if cost is still null, delete it and add it to the no sku list
                if(is_null($cost)) {
                    $this->noSkuCostInvReceive[$fac][$sku] = $this->facHashTable[$fac][$sku];
                    unset($this->facHashTable[$fac][$sku]);
                }
                else {
                    $this->facHashTable[$fac][$sku]['cost'] = $cost;
                }
                
            } // END OF: inner_loop_2
            
            $debug = 1;
            
        } // end of: OUTER_LOOP_1
        
        $debug = 1;
    }
    
    /**
     * Helper recursive function
     * The most recent cost can sometimes have a cost of 0, so I need to
     * recursively get cost, if the most recent cost is 0... recurse to the
     * next most recent cost and get it if it's not 0
     *
     * @param array $skuTable
     * @param array $datesSorted
     *
     * @return float
     */
    private function recursivePartitionCost(array $skuTable, array $datesSorted): ?float {
        if(0 === count($datesSorted)) {
            $ml = __METHOD__ . ' line: ' . __LINE__;
            $err = "\n\nThere is NOOOOOO cost at all for the SKU ~$ml\n\n";
            var_dump($skuTable);
            echo($err);
            return null;
        }
        $filter = function($v, $k) use ($datesSorted, $skuTable): bool {
            $mostRecent = $datesSorted[0];
            if($v['date'] == $mostRecent) {
                if('0' == $v['cost']) {
                    return false;
                }
                return true;
            }
            return false;
        };
        
        $mostRecentCost = array_values(array_filter($skuTable, $filter, ARRAY_FILTER_USE_BOTH));
        $cost = empty($mostRecentCost) ? null : (float)$mostRecentCost[0]['cost'];
        
        if(is_null($cost) || 0.0 == $cost) {
            $debug = 1;
            array_shift($datesSorted);
            return $this->recursivePartitionCost($skuTable, $datesSorted);
        }
        return $cost;
    }
}