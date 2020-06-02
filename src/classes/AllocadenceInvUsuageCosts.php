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
    
    private array $skuNoCost;
    
    private array $facHashTable;
    
    public function __construct() {
        parent::__construct();
        
        $this->titlesOrderDetails = new class() {
            public string $sku = 'SKU';
            public string $qty = 'Picked Qty';
            public string $fac = 'Ship From';
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
            // r = result
            public array $rAtl;
            public array $rBal;
            public array $rDen;
            public array $rEnf;
            public array $rSac;
        };
        
        //TODO: continue from here hwa@6-1-2020 10:47pm, the $facHashTable has been created,
        // now use it to figure out cost while looping over the orderdetails.csv
        foreach($this->orderDetails as $i => $order) {
            if(0 === $i) continue;
            
            $_fac = $order[$this->titlesOrderDetails->fac];
            $_qty = $order[$this->titlesOrderDetails->qty];
            
            // some SKUs contain '-cli'
            $_sku = $order[$this->titlesOrderDetails->sku];
            
            $debug = 1;
        }
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
        $facHashTable = [];
        
        // OUTER_LOOP_1 O(6*n)
        foreach($facCosts as $fac => $costSet) {
            $_sku = null;
            // inner_loop_1
            foreach($costSet as $i => $costs) {
                if(0 === $i) continue;
                
                $_sku = $costs[$this->titlesOrderDetails->sku];
                if(stripos($_sku, 'cli') !== false) {
                    $_sku = str_ireplace('-cli', '', $_sku);
                }
                $_cost = $costs [$this->titlesInvReceived->cost];
                $_date = $costs[$this->titlesInvReceived->date];
                
                $facHashTable[$fac][$_sku] [] = [
                    'sku' => $_sku, 'cost' => $_cost, 'date' => $_date, 'fac' => $fac,
                ];
            }
            
            // inner_loop_2
            foreach($facHashTable[$fac] as $sku => $skuTable) {
                $dates = array_column($skuTable, 'date');
                arsort($dates);
                $datesSorted = array_values($dates);
                
                $cost = $this->recursivePartitionCost($skuTable, $datesSorted);
                
                // search the bot scraped data for a supplier cost
                if(is_null($cost)) {
                    foreach($this->botSupplierInfo as $item) {
                        if($item['sku'] == $sku) {
                            $cost = (float)$item['unit_cost'];
                            break;
                        }
                    }
                }
                
                // if cost is still null, delete it and add it to the no sku list
                if(is_null($cost)) {
                    $this->skuNoCost[$fac][$sku] = $facHashTable[$fac][$sku];
                    unset($facHashTable[$fac][$sku]);
                }
                else {
                    $facHashTable[$fac][$sku]['cost'] = $cost;
                }
                
            } // END OF: inner_loop_2
            
            $debug = 1;
            
        } // end of: OUTER_LOOP_1
        $this->facHashTable = $facHashTable;
        $debug = 1;
    }
    
    /**
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