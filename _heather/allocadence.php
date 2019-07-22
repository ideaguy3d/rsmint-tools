<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 1/28/2019
 * Time: 1:29 PM
 */

// connect to main Ninja app
require __DIR__ . '/../vendor/autoload.php';

use Ninja\Auto\CsvParseModel;

$doSpicers = true;
$doVeritiv = true;
$pricingPath = 'C:\xampp\htdocs\ninja\_heather-sku\sku';
$veritivFile = 'pricing-veritiv.csv';
$veritivPricingArr = CsvParseModel::specificCsv2array($pricingPath, $veritivFile);
$spicersFile = 'pricing-spicers.csv';
$spicersPricingArr = CsvParseModel::specificCsv2array($pricingPath, $spicersFile);

$perLocationPricing = [
    // IMPORTANT - initialize field title row
    ['SKU', 'Supplier Name', 'Vendor Code', 'Unit Cost'],
];

$cleanUnitPrice = function(string $unitPrice): string {
    $unitPrice = preg_replace('/[\$na\/m]/i', '', $unitPrice);
    // DIVIDE BY 1,000 NOW
    $unitPrice = round(((float)$unitPrice) / 1000, 5);
    return $unitPrice;
};

if($doSpicers) {
    $spicersTransform = processPreppedSpicersData(
        $spicersPricingArr, $perLocationPricing, $cleanUnitPrice
    );
    
    CsvParseModel::export2csv(
        $spicersTransform, $pricingPath, 'spicers-per-location'
    );
}

if($doVeritiv) {
    $veritivTransform = processPreppedVeritivData(
        $veritivPricingArr, $perLocationPricing, $cleanUnitPrice
    );
    
    CsvParseModel::export2csv(
        $veritivTransform, $pricingPath, 'veritiv-per-location'
    );
}

/**Spicers
 *
 * Will create data with fields that match Allocadence's "Supplier Data Import"
 *
 * @param array $spicersPricingArr
 * @param array $perLocationPricing
 * @param Closure $cleanUnitPrice
 *
 * @return array
 */
function processPreppedSpicersData(
    array $spicersPricingArr, array $perLocationPricing, Closure $cleanUnitPrice
): array {
    $spicers = 'Spicers - ';
    
    // HARD CODED index's because I'm manually making the CSV
    $skuIdx = 0;
    $supplierNameIdx = 1;
    $vendorCodeIdx = 2;
    $unitCostIdx = 3;
    
    // OUTER LOOP, loop over records, start $i at 1 to skip field title row
    for($i = 1; $i < count($spicersPricingArr); $i++) {
        $record = $spicersPricingArr[$i];
        $offset = count($perLocationPricing);
        
        // INNER-LOOP, hard coded 2 because spicers only has 2 possible locations
        for($j = 0; $j < 2; $j++) {
            $sku = $record[0];
            
            // figure out per location calculations
            if($j === 0) {
                $perLocationPricing[$offset][$skuIdx] = $sku;
                $perLocationPricing[$offset][$supplierNameIdx] = $spicers . 'Sacramento';
                $perLocationPricing[$offset][$vendorCodeIdx] = 'SPI_SAC';
                
                $perLocationPricing[$offset][$unitCostIdx] = $cleanUnitPrice($record[1]);
            }
            else if($j === 1) {
                $perLocationPricing[$offset + 1][$skuIdx] = $sku;
                $perLocationPricing[$offset + 1][$supplierNameIdx] = $spicers . 'Denver';
                $perLocationPricing[$offset + 1][$vendorCodeIdx] = 'SPI_DEN';
                
                // DIVIDE BY 1,000 NOW
                $perLocationPricing[$offset + 1][$unitCostIdx] = $cleanUnitPrice($record[2]);
            }
            
        } // END OF: inner-loop
        
        $break = 'point';
        
    } // END OF: outer-loop
    
    return $perLocationPricing;
}

/**Veritiv
 *
 * Will create data with fields for Allocadence's "Items Data Import"
 *
 * @param array $veritivPricingArr
 * @param array $perLocationPricing
 * @param Closure $cleanUnitPrice
 *
 * @return array
 */
function processPreppedVeritivData(
    array $veritivPricingArr, array $perLocationPricing, Closure $cleanUnitPrice
): array {
    $veritiv = 'Veritiv - ';
    
    // HARD CODED index's because I manually making the CSV
    $skuIdx = 0;
    $supplierNameIdx = 1;
    $vendorCodeIdx = 2;
    $unitCostIdx = 3;
    
    // OUTER-LOOP, loop over records, start $i at 1 to skip field title row
    for($i = 1; $i < count($veritivPricingArr); $i++) {
        $record = $veritivPricingArr[$i];
        $offset = count($perLocationPricing);
        
        // INNER-LOOP, hard coded 4 because Veritiv only has 4 possible locations
        for($j = 0; $j < 4; $j++) {
            // HARD CODED to "pricing-veritiv.csv" column order
            $sku = $record[8];
            
            // figure out per location data
            if($j === 0) {
                // Sacramento
                $perLocationPricing[$offset][0] = $sku;
                $perLocationPricing[$offset][$supplierNameIdx] = $veritiv . 'Sacramento';
                $perLocationPricing[$offset][$vendorCodeIdx] = 'VER_SAC';
                
                // HARD CODED because I am referencing the data file directly
                $perLocationPricing[$offset][$unitCostIdx] = $cleanUnitPrice($record[10]);
            }
            else if($j === 1) {
                // Denver
                $perLocationPricing[$offset + 1][$skuIdx] = $sku;
                $perLocationPricing[$offset + 1][$supplierNameIdx] = $veritiv . 'Denver';
                $perLocationPricing[$offset + 1][$vendorCodeIdx] = 'VER_DEN';
                
                // HARD CODED because I am referencing the data file directly
                $perLocationPricing[$offset + 1][$unitCostIdx] = $cleanUnitPrice($record[11]);
            }
            else if($j === 2) {
                // Atlanta
                $perLocationPricing[$offset + 2][$skuIdx] = $sku;
                $perLocationPricing[$offset + 2][$supplierNameIdx] = $veritiv . 'Atlanta';
                $perLocationPricing[$offset + 2][$vendorCodeIdx] = 'VER_ATL';
                
                // HARD CODED because I am referencing the data file directly
                $perLocationPricing[$offset + 2][$unitCostIdx] = $cleanUnitPrice($record[12]);
            }
            else if($j === 3) {
                // Dallas
                $perLocationPricing[$offset + 3][$skuIdx] = $sku;
                $perLocationPricing[$offset + 3][$supplierNameIdx] = $veritiv . 'Dallas';
                $perLocationPricing[$offset + 3][$vendorCodeIdx] = 'VER_DAL';
                
                // HARD CODED because I am referencing the data file directly
                $perLocationPricing[$offset + 3][$unitCostIdx] = $cleanUnitPrice($record[13]);
            }
            
            $breakpoint = 0;
        } // END OF: inner loop "it dynamically creates per location fields"
        
        $breakpoint = 0;
        
    } // END OF: outer loop
    
    return $perLocationPricing;
}



$breakpoint = 0;





//