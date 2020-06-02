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
    
    private array $i_atlCosts;
    
    private array $i_balCosts;
    
    private array $i_denCosts;
    
    private array $i_enfCosts;
    
    private array $i_sacCosts;
    
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
        
        
    }
}