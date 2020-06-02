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
            CsvParseModel::specificCsv2array($this->downloadedFiles, $ordersFilePath)
        );
    }
    
    /**
     * Create the CSV files for each facility
     */
    public function calcInUsageCosts(): void {
    
    }
}