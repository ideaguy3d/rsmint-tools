<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 12/4/2018
 * Time: 11:10 AM
 *
 *  This is meant to run from localhost. I am just going
 * to use this script to upload our CSV data to SQL server.
 *
 *  Ideally this code should be invoked from the debugger,
 *  but the CLI would work to.
 */

require '..\vendor\autoload.php';

// communicate with ninja
$localUri = 'http://localhost/ninja/app/';
$client = new \GuzzleHttp\Client(['base_uri' => $localUri]);

// quick test request to make sure Guzzle is wired up to ninja locally
// $testReq = $client->get('pick/tickets/read/v1');
// $testReqData = json_decode($testReq->getbody()->getContents());

// 'ci' folder should only ever have 1 CSV file
$localCsvPath = 'C:\xampp\htdocs\ninja\_heather-sku\ci';
$csvData = \Ninja\Auto\CsvParseModel::csv2array($localCsvPath);
// $csvDataVeritiv = veritiv($csvData);
$csvDataSpicer = spicer($csvData);

$break = "point";

function spicer(array $data): array {
    $spicerArr = [];
    
    $locations = [
        1 => ['id' => 'Sacramento'],
        2 => ['id' => 'Denver'],
    ];
    
    for($i = 0, $s = -1; $i < count($data); $i++, $s++) {
        $record = $data[$i];
        
        if($i === 0) {
            // hard coding index's because I am referencing raw CSV file
            // 1 = sacramento, 2 = denver, 3 = atlanta, 4 = dallas
            $locations[1]['raw_title'] = $record[1];
            $locations[2]['raw_title'] = $record[2];
            
            $spicerArr[$i] = [
                'sku' => 'sku',
                'spicer_cost' => 'spicer_cost',
                'spicer_location' => 'spicer_location',
                'spicer_location_info' => 'spicer_location_info',
                'skid_box' => 'skid_box',
                'brand' => 'brand',
                'additional_cost_info' => 'additional_cost_info',
            ];
        }
        else {
            //------------------
            // -- INNER LOOP --
            //------------------
            // WORST CASE = 2
            for($g = 0; $g < 2; $g++) {
                $go = $i + ($g + $s);
                
                //// ref the record directly
                $spicerArr[$go]['sku'] = $record[0];
                //// dynamically generate additional record data
                $spicerArr[$go]['spicer_location'] = $locations[$g + 1]['id'];
                $spicerArr[$go]['spicer_location_info'] = $locations[$g + 1]['raw_title'];
                
                // MOST IMPORTANT! Figure out cost per location:
                // $g = 0 = sacramento i.e.     cost_column = 1
                // $g = 1 = denver i.e.         cost_column = 2
                switch($g) {
                    case 0:
                        $spicerArr[$go]['spicer_cost'] = $record[1];
                        break;
                    case 1:
                        $spicerArr[$go]['spicer_cost'] = $record[2];
                        break;
                }
    
                //// ref the record directly
                $spicerArr[$go]['skid_box'] = $record[4];
                $spicerArr[$go]['brand'] = $record[5];
                $spicerArr[$go]['additional_cost_info'] = $record[3];
                
            } // END OF: inner-loop
        }
        
    } // END OF: outer-loop
    
    return $spicerArr;
}

// transforming csv data to match db table structure for veritiv
function veritiv(array $csvData): array {
    // function fields
    $veritivArr = [];
    
    // Our locations, indexes are in the same order as the CSV file for Veritiv
    $locations = [
        1 => ['id' => 'Sacramento'],
        2 => ['id' => 'Denver'],
        3 => ['id' => 'Atlanta'],
        4 => ['id' => 'Dallas'],
    ];
    
    // state manager
    $state = [
        'offset' => 0,
        // hard code to 4 because there are 4 locations per group
        // Sacramento, Denver, Atlanta, Dallas
        'transcend' => 4,
    ];
    
    // iterate over CSV data
    for($i = 0, $s = -3; $i < count($csvData); $i++, ($s += 3)) {
        $record = $csvData[$i];
        
        if($i === 0) { // if we're on header row
            $headerRowOrig = $record;
            
            // hard coding index's because I am referencing raw CSV file
            $locations[1]['raw_title'] = $headerRowOrig[1];
            $locations[2]['raw_title'] = $headerRowOrig[2];
            $locations[3]['raw_title'] = $headerRowOrig[3];
            $locations[4]['raw_title'] = $headerRowOrig[4];
            
            // the db table fields, $i should be 0
            $veritivArr[$i] = [
                'sku', 'veritiv_cost', 'veritiv_location', 'veritiv_location_info',
                'brand_item_number', 'skid_box', 'brand', 'additional_cost_info',
            ];
        }
        //----------------------------------------------
        // else we're Not on header row, do other stuff
        else {
            //------------------
            // -- INNER LOOP --
            //------------------
            for($g = 0; $g < 4; $g++) {
                // group offset formula
                $go = $i + ($g + $s);
                
                // ref the record directly
                $veritivArr[$go]['sku'] = $record[0];
                $veritivArr[$go]['veritiv_location'] = $locations[$g + 1]['id'];
                $veritivArr[$go]['veritiv_location_info'] = $locations[$g + 1]['raw_title'];
                
                // ref the record directly
                $veritivArr[$go]['brand_item_number'] = $record[5];
                
                // MOST IMPORTANT! Figure out cost per location:
                // $g = 0 = sacramento i.e.     cost_column = 1
                // $g = 1 = denver i.e.         cost_column = 2
                // $g = 2 = atlanta i.e.        cost_column = 3
                // $g = 3 = dallas i.e.         cost_column = 4
                switch($g) {
                    case 0:
                        $veritivArr[$go]['veritiv_cost'] = $record[1];
                        break;
                    case 1:
                        $veritivArr[$go]['veritiv_cost'] = $record[2];
                        break;
                    case 2:
                        $veritivArr[$go]['veritiv_cost'] = $record[3];
                        break;
                    case 3:
                        $veritivArr[$go]['veritiv_cost'] = $record[4];
                        break;
                }
    
                //// ref the record directly
                $veritivArr[$go]['skid_box'] = '';
                $veritivArr[$go]['brand'] = '';
                $veritivArr[$go]['additional_cost_info'] = '';
                
            } // END OF: inner-loop
            
            // SUPER IMPORTANT, increment state. This ensures a constant offset.
            $state['offset'] += 3;
        }
    } // END OF: outer-loop
    return $veritivArr;
}

// initial attempt to transform csv data to match the db table structure
// A function that attempts to use the idea of a state transcend and a state
// offset. It will need a reference to the outer variables
function stateOffsetTranscend(array $record, array &$veritivArr, array &$state, array $locations): void {
    // Worst Case = 4, $g determines which group we're in
    // 0 = sac, 1 = denver, 2 = atlanta, 3 = dallas
    for($g = 0; $g < 4; $g++) {
        if($g === 0) { // initial state
            // group transcend
            $gt = $g + $state['transcend'];
            
            $veritivArr[$gt]['sku'] = $record[0]; // ref the record directly
            $veritivArr[$gt]['veritiv_cost'] = $record[1];
            $veritivArr[$gt]['veritiv_location'] = $locations[$g + 1]['id'];
            $veritivArr[$gt]['veritiv_location_info'] = $locations[$g + 1]['raw_title'];
            // ref the record directly
            $veritivArr[$gt]['brand_item_number'] = $record[5]; // ref the record directly
            
            // Spicer specific
            $veritivArr[$gt]['skid_box'] = '';
            // Spicer specific
            $veritivArr[$gt]['brand'] = '';
            // Spicer specific
            $veritivArr[$gt]['additional_cost_info'] = '';
            
            $state['transcend'] = $state['offset'];
        }
        else {
            // group offset
            $go = $g + $state['offset'];
            
            $veritivArr[$go]['sku'] = $record[0]; // ref the record directly
            $veritivArr[$go]['veritiv_cost'] = $record[1];
            $veritivArr[$go]['veritiv_location'] = $locations[$g + 1]['id'];
            $veritivArr[$go]['veritiv_location_info'] = $locations[$g + 1]['raw_title'];
            $veritivArr[$go]['brand_item_number'] = $record[5]; // ref the record directly
            
            // Spicer specific
            $veritivArr[$go]['skid_box'] = '';
            // Spicer specific
            $veritivArr[$go]['brand'] = '';
            // Spicer specific
            $veritivArr[$go]['additional_cost_info'] = '';
        }
        
    } // END OF inner-loop
    
} // END OF: function stateOffsetTranscend(){}

// send sku data to api
$insert2table = 'vendor-spicers';

/**  Uncomment and chunk data ONLY if it's larger than 124 records **/
//$arrChunked = array_chunk($csvDataVeritiv, 124);
//foreach($arrChunked as $chunk) {
//    $skuPostRequest = $client->request('POST', "sku/post/$insert2table", [
//        'form_params' => $chunk,
//    ]);
//}

$skuPostRequest = $client->request('POST', "sku/post/$insert2table", [
    'form_params' => $csvDataSpicer,
]);

$skuPostRequestData = $skuPostRequest->getBody()->getContents();
//-- have to json_decode() twice because I json_encode() in api to just get back what I post (which I use for debugging)
//-- uncomment if NOT debugging
//$skuPostRequestData = json_decode(json_decode($skuPostRequestData));

//$skuPostRequestData = json_decode($skuPostRequestData);

$break = "point";






//