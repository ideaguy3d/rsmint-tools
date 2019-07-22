<?php
// will parse PDF that contains Fire Dept data for feb 2019
declare(strict_types=1);
// connect to main Ninja app
require __DIR__ . '/../vendor/autoload.php';

use \Smalot\PdfParser\Parser as pdf;
use \Ninja\Auto\CsvParseModel;

class FireDeptPdfParse
{
    
    private $pdfTextData;
    private $dataNormalized;
    private $pdfBook;
    
    public function __construct($pdfBook, $pdfTextData = "") {
        $this->pdfTextData = explode("\r\n", $pdfTextData);
        $this->pdfBook = $pdfBook;
    }
    
    public function parseBook(): array {
        $dataModel = [
            // 0
            'redstone_print_and_mail_id' => null,
            // 1
            'fire_dept' => null,
            // 2
            'fire_chief' => null,
            // 3
            'full_address' => null,
            // 4
            'address' => null,
            // 5
            'city' => null,
            // 6
            'state' => null,
            // 7
            'zip' => null,
            // 8
            'office_number' => null,
            // 9
            'fax_number' => null,
            // 10
            'general_email' => null,
            // 11
            'chief_email' => null,
            // 12
            'dept_website' => null,
            // 13
            'city_website' => null,
            // 14
            'macs_designator' => null,
            // 15
            'personnel' => null,
            // 16
            'county' => null,
            // 17
            'fire_dept_id' => null,
            // 18
            'data_as_of' => null,
        ];
        
        $pdfBook = $this->pdfBook;
        $redstoneId = 0;
        
        // OUTER LOOP START - looping over pages
        for($p = 1; $p < count($pdfBook); $p++) {
            /**
             * ~ this'll be a so called "BOX ROW" ~
             * r1    Air National Guard Fire Dept. Christopher Diaz , Fire Chief
             * r2    5323 E. McKinley Ave.
             * r3    Fresno , CA   93727
             * r4    Office:  (559) 454- 5315   Fax: (559) 454- 5329
             * r5    General Email:
             * r6    Chief Email:  christopher.diaz@cafres.ang.af.mil
             * r7    Dept. Web:
             * r8    City Web:
             * r9    MACS Designator: REE    Personnel: Paid
             * r10   County:  Fresno   Fire Dept. ID: 10800
             **/
            
            // each row is a so called "BOX ROW", so will need to maintain a constant offset
            $pageNum = "page$p";
            $page = $pdfBook[$pageNum];
            $pageRows = explode("\r\n", $page);
            $pageRowsGrouped = [];
            
            $pageRows = array_filter($pageRows, function($elem) {
                return (strlen($elem) > 3);
            });
            
            // delete unneeded first 4 recs
            for($d = 0; $d < 4; $d++) {
                array_shift($pageRows);
            }
            
            if(count($pageRows) === 120) {
                $pageRowsGrouped = array_chunk($pageRows, 10);
            }
            else {
                $groupCount = 0;
                foreach($pageRows as $pageRow) {
                    $edge1case = "\t \t \t \t";
                    
                    if(strpos($pageRow, $edge1case) !== false) {
                        $edge = explode($edge1case, $pageRow);
                        $pageRowsGrouped[$groupCount] [] = trim($edge[0]);
                        $groupCount++;
                        $pageRowsGrouped[$groupCount] [] = trim($edge[1]);
                        $break = 'point';
                    }
                    else if((strpos($pageRow, 'County:') === false)) {
                        $pageRowsGrouped[$groupCount] [] = $pageRow;
                        $countTracker = (count($pageRowsGrouped[$groupCount]) > 9);
                        if($countTracker) {
                            $break = 'point';
                        }
                    }
                    else if((strpos($pageRow, 'County:') !== false)) {
                        $pageRowsGrouped[$groupCount] [] = $pageRow;
                        $groupCount++;
                    }
                }
                $break = 'point';
            }
            
            // INNER LOOP OVER PAGE ROWS
            // extract fields from raw string rows in the grouped array
            for($i = 0; $i < count($pageRowsGrouped); $i++) {
                $group = $pageRowsGrouped[$i];
                $r1 = $group[0];
                $r2 = $group[1];
                $r3 = $group[2];
                $r4 = $group[3];
                $r5 = $group[4];
                $r6 = $group[5];
                $r7 = $group[6];
                $r8 = $group[7];
                $r9 = $group[8] ?? null;
                $r10 = $group[9] ?? null;
                
                
                // [redstone_print_and_mail_id], [fire_dept], [fire_chief]
                $dataModel['redstone_print_and_mail_id'] = $redstoneId++;
                $pivots = explode("\t \t", $r1);
                if(count($pivots) < 2) {
                    $pivots = explode("\t\t", $r1);
                }
                
                
                if(count($pivots) >= 2) {
                    $pivots = array_filter($pivots, function($elem) {
                        return (strlen($elem) > 2);
                    });
                    
                    // re-index because array_filter makes array index NOT contiguous
                    $pivotsNew = [];
                    foreach($pivots as $pivot) {
                        $pivotsNew [] = $pivot;
                    }
                    $fireDept = $pivotsNew[0];
                    $fireChief = $pivotsNew[1];
                    
                    $dataModel['fire_dept'] = trim($fireDept);
                    $dataModel['fire_chief'] = trim($fireChief);
                }
                
                
                /*
                // old way of extracting these fields
                $removeLen = strlen($dataModel['fire_dept']) + 2;
                $row = substr_replace($r1, '', 0, $removeLen);
                $pivot = strpos($row, "\t") + 1; // update pivot
                $tempFireChief = substr($row, $pivot, strpos($row, ',') - 1);
                if(is_bool($tempFireChief)) {
                    echo "\n__>> ERROR - temp fire chief should NOT be a bool!! debug.\n";
                }
                else {
                    $dataModel['fire_chief'] = trim($tempFireChief);
                }
                */
                
                if(!(count($pivots) > 2)) {
                    // [full_address], [address], [city], [state], [zip]
                    // ... at this point the full address is across 2 rows
                    $dataModel['address'] = trim($r2);
                    $length = strpos($r3, ',');
                    if($length) {
                        $dataModel['city'] = trim(substr($r3, 0, $length));
                    }
                    else {
                        $dataModel['city'] = "ERROR no city field in $r3 ~line 464 ish";
                    }
                    $tempRow = substr_replace($r3, '', 0, (strlen($dataModel['city']) + 1));
                    $tempState = substr(trim($tempRow), 1, 3);
                    if(!is_bool($tempState)) {
                        $dataModel['state'] = trim($tempState);
                    }
                    else {
                        $dataModel['state'] = "ERROR trying to extract [state] from $tempRow ~line 472 ish";
                    }
                    $dataModel['zip'] = trim(substr($tempRow, -7));
                    $dataModel['full_address'] = "{$dataModel['address']} {$dataModel['city']} {$dataModel['state']} {$dataModel['zip']}";
                }
                else if(isset($pivotsNew) && (count($pivotsNew) > 2)) {
                    $tempAddress = $pivotsNew[2] ?? null;
                    $tempCityStZip = $pivotsNew[3] ?? null;
                    
                    if($tempAddress && $tempCityStZip) {
                        $address = trim($tempAddress);
                        $cityStZip = trim($tempCityStZip);
                        $dataModel['address'] = $address;
                        $dataModel['city'] = trim(substr($cityStZip, 0, strpos($cityStZip, ',')));
                        $tempRow = trim(substr_replace($cityStZip, '', 0, (strlen($dataModel['city']) + 1)));
                        $dataModel['state'] = "{$tempRow[0]}{$tempRow[1]}";
                        $dataModel['zip'] = trim(substr($tempRow, -5));
                        $dataModel['full_address'] = "{$dataModel['address']} {$dataModel['city']}" .
                            " {$dataModel['state']} {$dataModel['zip']}";
                    }
                    else {
                        $address = 'ERROR';
                        $cityStZip = 'ERROR';
                    }
                    
                    $break = 'point';
                }
                
                // [office_number], [fax_number]
                try {
                    if((strpos(strtolower($r4), 'office') !== false)) {
                        $officeFax = explode('fax:', strtolower($r4));
                        $office = stristr($officeFax[0], ':');
                        $fax = $officeFax[1];
                        
                        // match office number
                        preg_match("/\(\d{3}\)\s?\d{3}.+\d{3}/", $office, $matches);
                        $office = $matches[0] ?? 'no_office';
                        $dataModel['office_number'] = $office;
                        
                        // match fax number
                        preg_match("/\(\d{3}\)\s?\d{3}.+\d{3}/", $fax, $matches);
                        $fax = $matches[0] ?? "no_fax";
                        $dataModel['fax_number'] = $fax;
                    }
                    else if((strpos(strtolower($r2), 'office') !== false)) {
                        $officeFax = explode('fax:', strtolower($r2));
                        $office = stristr($officeFax[0], ':');
                        $fax = $officeFax[1];
                        
                        // match office number
                        preg_match("/\(\d{3}\)\s?\d{3}.+\d{3}/", $office, $matches);
                        $office = $matches[0] ?? 'no_office';
                        $dataModel['office_number'] = $office;
                        
                        // match fax number
                        preg_match("/\(\d{3}\)\s?\d{3}.+\d{3}/", $fax, $matches);
                        $fax = $matches[0] ?? "no_fax";
                        $dataModel['fax_number'] = $fax;
                    }
                    else {
                        $message = "trying to extract [office] and [fax] but not on correct row";
                        throw new Exception("__>> ERROR - $message");
                    }
                }
                catch(Exception $e) {
                    $message = $e->getMessage();
                    //echo $e->getMessage();
                }
                
                
                // [general_email]
                $email = trim(substr(stristr($r5, ':'), 1));
                $email = $email ?? 'no_email';
                $dataModel['general_email'] = $email;
                
                
                // [chief_email]
                $email = trim(substr(stristr($r6, ':'), 1));
                $email = $email ?? 'no_email';
                $dataModel['chief_email'] = $email;
                
                
                // [dept_website]
                $tempDeptWeb = stristr($r7, ':');
                if(is_bool($tempDeptWeb)) {
                    $dataModel['dept_website'] = 'ERROR';
                }
                else {
                    $deptWebsite = trim(substr($tempDeptWeb, 1));
                    $deptWebsite = $deptWebsite ?? 'no_dept_site';
                    $dataModel['dept_website'] = $deptWebsite;
                }
                
                
                // [city_website]
                $tempCityWeb = stristr($r8, ':');
                if($tempCityWeb) {
                    $cityWebsite = trim(substr($tempCityWeb, 1));
                    $cityWebsite = $cityWebsite ?? 'no_city_site';
                    $dataModel['city_website'] = $cityWebsite;
                }
                else {
                    $break = 'point';
                }
                
                
                // [macs_designator], [personnel]
                $keyWord = 'personnel:';
                if($r9 && strpos(strtolower($r9), $keyWord) !== false) {
                    $mdPer = explode('personnel:', strtolower($r9));
                    $macsDes = $mdPer[0];
                    $personnel = $mdPer[1];
                    $tempMacsDes = stristr($macsDes, ':');
                    if(is_bool($tempMacsDes)) {
                        $break = 'point';
                    }
                    else {
                        $macsDes = trim(substr($tempMacsDes, 1));
                    }
                    
                    $dataModel['macs_designator'] = strtoupper($macsDes);
                    $personnel = trim($personnel);
                    $dataModel['personnel'] = ucwords($personnel);
                }
                
                
                // [county], [fire_dept_id]
                if($r10) {
                    $countyFireId = explode('Fire Dept. ID:', $r10);
                    $county = $countyFireId[0] ?? "ERROR extracting [county] field from: $r10";
                    $tempCounty = stristr($county, ':');
                    if(!is_bool($tempCounty)) {
                        $county = trim(substr($tempCounty, 1));
                        $dataModel['county'] = $county;
                        $fireDeptId = $countyFireId[1] ?? "ERROR extracting [fire_dept_id] field from: $r10";
                        $fireDeptId = trim($fireDeptId);
                        $dataModel['fire_dept_id'] = $fireDeptId;
                    }
                    else {
                        $dataModel['county'] = 'ERROR';
                        $dataModel['fire_dept_id'] = 'ERROR';
                    }
                }
                
                
                // [data_as_of]
                $dataModel['data_as_of'] = 'Feb 2019';
                
                //--------------------------------
                // -- ADD DATA TO class object --
                //--------------------------------
                $this->dataNormalized [] = $dataModel;
                
                $break = 'point';
                
            } // END OF: inner-looping over grouped block data from PDF page
            
            $break = 'point'; // next page
            
        } // END OF: outer-looping over ~100 pages of raw globbed rows from PDF
        
        //-- REMEMBER TO ADD HEADER ROW:
        array_unshift($this->dataNormalized, array_keys($dataModel));
        
        return $this->dataNormalized;
        
    } // END OF: parseBook()
    
} // END OF: class FireDeptPdfParse{}


//-----------------------------------------------------------------------------------------
// -------------------------------- SCRIPT SETUP COMPLETE --------------------------------
//-----------------------------------------------------------------------------------------

//-- script declarations:
$pdfText = null;
$fdParser = null;
$pdfParseResult /* array */ = null;
$pdfBook = [];
$parsePdfFromScratch = false;

// parsing the ENTIRE pdf takes like 15 seconds, so I just parse it once,
// then copy and paste the results to local files.
if($parsePdfFromScratch) {
    //-- script initializations:
    $pdfName = './feb19_fire_dept_data.pdf';
    $pdfParser = new pdf();
    
    try {
        $pdf = $pdfParser->parseFile($pdfName);
        $pdfText = $pdf->getText();
        $pdfPages = $pdf->getPages();
        $count = 1;
        
        foreach($pdfPages as $pdfPage) {
            $pdfBook[('page' . $count)] = $pdfPage->getText();
            $count++;
        }
        
        $break = "point";
    }
    catch(\Exception $e) {
        exit("\n __>> ERROR - pdf parse unable to parse PDF because:\n" . $e->getMessage());
    }
}
else {
    $pdfText = file_get_contents('./firedata-print_r.txt');
    $pdfBook = require './var_export-pdfBook.php';
}

// now that PDF text has been extracted, instantiate the class
$fdParser = new FireDeptPdfParse($pdfBook, $pdfText);
$pdfParseResult = $fdParser->parseBook();

CsvParseModel::export2csv($pdfParseResult, './', 'rs-fire-dept-pdf-parse');

$break = 'point';








// END OF: php file