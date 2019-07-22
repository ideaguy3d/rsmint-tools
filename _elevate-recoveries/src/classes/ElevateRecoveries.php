<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 10/24/2018
 * Time: 4:00 PM
 */

namespace Rsm\ElevateRecoveries;

class ElevateRecoveries implements ElevateRecoveriesInterface
{
    private $rawDataArr;
    private $csv = [];
    private $csvExcess = [];
    private $csvAppendsTracker = [];
    private $rawDataNoHeader;
    private $rawDataHeaderRow;
    private $headerRowKey;
    private $rightShiftGroupUpLimit = 12;
    
    // start at 1 because the appends formula will always start at 1
    private $rightShiftUpGroupAppends = 1;
    
    // This holds the number of fields there are originally before
    // "right shift up merge" appends additional fields to these original fields
    private $baseFieldsCount;
    // This will depend on how many fields Justin tells me need to be
    // "right shift up merged", for now I will assume all but the "duplicate identifier fields",
    // so 35 if there are 41 total fields
    // OR just the field values that change per dupe rec so 8
    // OR 6, the fields Lindsey and I discussed :)
    private $numberOfFieldsToAppend = 6;
    
    public function __construct(array $rawDataArr) {
        $this->rawDataArr = $rawDataArr;
        // Just count how many fields there are in the header row
        // to know how many "base" fields there are.
        $this->baseFieldsCount = count($rawDataArr[0]);
        $this->rawDataHeaderRow = array_shift($rawDataArr);
        $this->rawDataNoHeader = $rawDataArr;
    }
    
    // TODO: sort the array based on something useful
    
    public function elevate(): array {
        // $data is the actual row/record in CSV
        foreach($this->rawDataArr as $data) {
            // TODO: implement 'LIKE' algorithm for field titles !!!!!!!!!!!!!!!!!!
            // HARD_CODED [5], [6] for [Name], [Address Street 1]
            $key = $data[5] . '__' . $data[6];
            
            // If $key hash hasn't been created then hash and initialize it
            if(!isset($this->csv[$key])) {
                $this->csv[$key] = $data;
                $this->csvAppendsTracker[$key] = 0;
                $this->csvExcess[$key] = [];
                if(!isset($this->headerRowKey)) {
                    $this->headerRowKey = $key;
                }
            }
            // Else rather than overwrite hash map key value, merge more data to it
            // ALL of the "right shift up merge" work will be done here !!
            else {
                $mergeData = $this->hardCodeFields($data);
                ++$this->csvAppendsTracker[$key];
                // if we haven't exceeded amount of dupe recs to "right shift up merge"
                if($this->csvAppendsTracker[$key] <= $this->rightShiftGroupUpLimit) {
                    $this->csv[$key] = array_merge($this->csv[$key], $mergeData);
                    
                    // for better unit testing, will make trackAppends its' own function
                    $this->trackAppends(count($this->csv[$key]));
                }
                // start writing excess duplicate records (more than 10 dupe recs) to a separate csv file
                else {
                    $this->csvExcess[$key] = array_merge($this->csvExcess[$key], $mergeData);
                    $break = "point";
                }
            }
        }
        
        echo "\nPHP 7 has finished processing data, will now dynamically generate headers.\n";
        
        $this->dynamicallyCreateHeaders();
        
        $this->csvExcess = array_filter($this->csvExcess, function($e) { return (count($e) > 0); });
        
        //-- this will prepend the incorrect field titles to the excess data:
        // array_unshift($this->csvExcess, $this->rawDataHeaderRow);
        
        $break = "point";
        
        return $this->csv;
    }
    
    // Working on data set from "old-elr-v1"
    // This function is hard coded to column order >:\
    // Should implement a simple "LIKE" algorithm to dynamically find fields index
    // rather than depend on hard coded column order indexes
    public function elevateV1(): array {
        foreach($this->rawDataArr as $data) {
            // TODO: implement 'LIKE' algorithm for field titles !!!!!!!!!!!!!!!!!!
            // HARD_CODED [1], [2] for [Name], [Address Street 1]
            $key = $data[5] . '__' . $data[6];
            
            if(!isset($this->csv[$key])) {
                // dynamically hash map key and initialize it
                $this->csv[$key] = $data;
                $this->csvAppendsTracker[$key] = 0;
                $this->csvExcess[$key] = [];
                if(!isset($this->headerRowKey)) {
                    $this->headerRowKey = $key;
                }
            }
            // else rather than overwrite hash map key value, merge more data to it
            else {
                ++$this->csvAppendsTracker[$key];
                
                if($this->csvAppendsTracker[$key] <= $this->rightShiftGroupUpLimit) {
                    // TODO: implement 'LIKE' algorithm for field titles !!!!!!!!!!!!!!!!!!
                    $originalClientAccountNumber = $data[7]; // [Original Client Account Number] field
                    $accountBalance = $data[8];              // [Account Balance] field
                    $settlementRate = $data[9];              // [Settlement Rate] field
                    $remarksCurrentCreditor = $data[10];     // [Remarks Current Creditor] field
                    $originalCreditorName = $data[11];       // [Original Creditor Name] field
                    $groupNumber = $data[12];                // [Group Number] field
                    $serviceDate = $data[13];                // [Service Date] field
                    
                    $mergeData = [
                        $originalClientAccountNumber,
                        $accountBalance,
                        $settlementRate,
                        $remarksCurrentCreditor,
                        $originalCreditorName,
                        $groupNumber,
                        $serviceDate,
                    ];
                    
                    $this->csv[$key] = array_merge($this->csv[$key], $mergeData);
                    
                    // for better unit testing, will make append tracker its' own function
                    $this->trackAppends(count($this->csv[$key]));
                }
                // start writing excess duplicate records (more than 10 dupe recs) to a separate csv file
                else {
                    // TODO: implement 'LIKE' algorithm for field titles !!!!!!!!!!!!!!!!!!
                    $originalClientAccountNumber = $data[7]; // [Original Client Account Number] field
                    $accountBalance = $data[8];              // [Account Balance] field
                    $settlementRate = $data[9];              // [Settlement Rate] field
                    $remarksCurrentCreditor = $data[10];     // [Remarks Current Creditor] field
                    $originalCreditorName = $data[11];       // [Original Creditor Name] field
                    $groupNumber = $data[12];                // [Group Number] field
                    $serviceDate = $data[13];                // [Service Date] field
                    
                    $mergeData = [
                        $originalClientAccountNumber,
                        $accountBalance,
                        $settlementRate,
                        $remarksCurrentCreditor,
                        $originalCreditorName,
                        $groupNumber,
                        $serviceDate,
                    ];
                    
                    $this->csvExcess[$key] = array_merge($this->csvExcess[$key], $mergeData);
                    
                    $break = "point";
                }
            }
        }
        
        echo "\nPHP 7 has finished processing data.\n";
        
        // dynamically generate additional fields titles for header row [$this->csv]
        for($i = 0; $i < $this->rightShiftUpGroupAppends; $i++) {
            // HARD_CODED [7]-[13] are the fields being appended
            $headerMergeData = [
                $this->rawDataHeaderRow[7] . ' ' . ($i + 1),
                $this->rawDataHeaderRow[8] . ' ' . ($i + 1),
                $this->rawDataHeaderRow[9] . ' ' . ($i + 1),
                $this->rawDataHeaderRow[10] . ' ' . ($i + 1),
                $this->rawDataHeaderRow[11] . ' ' . ($i + 1),
                $this->rawDataHeaderRow[12] . ' ' . ($i + 1),
                $this->rawDataHeaderRow[13] . ' ' . ($i + 1),
            ];
            
            $this->csv[$this->headerRowKey] = array_merge($this->csv[$this->headerRowKey], $headerMergeData);
            
            $break = "point";
        }
        
        $this->csvExcess = array_filter($this->csvExcess, function($e) { return (count($e) > 0); });
        
        //-- this will prepend the incorrect field titles to the excess data:
        // array_unshift($this->csvExcess, $this->rawDataHeaderRow);
        
        $break = "point";
        
        return $this->csv;
    }
    
    public function getCsvExcess(): array {
        return $this->csvExcess;
    }
    
    public function trackAppends(int $arrSize): void {
        $appends = ($arrSize - $this->baseFieldsCount) / $this->numberOfFieldsToAppend;
        if($appends > $this->rightShiftUpGroupAppends) {
            $this->rightShiftUpGroupAppends++;
        }
    }
    
    //-- Dynamically generate additional fields titles for header row [$this->csv]
    // This is EXTREMELY EXTREMELY HARD CODED, all needed vars are class fields
    private function dynamicallyCreateHeaders(): void {
        // SUPER HARD CODED !! >:(
        // if 6, we're going with the fields Lindsey and I discussed :), but tis still HARD CODED >:(
        if($this->numberOfFieldsToAppend === 6) {
            // transform pertinent raw data header row fields
            $this->csv['Name__Address Street 1'][3] = 'acct_num';
            $this->csv['Name__Address Street 1'][14] = 'orig_acct';
            $this->csv['Name__Address Street 1'][17] = 'bal_due';
            $this->csv['Name__Address Street 1'][19] = 'sett_amt';
            $this->csv['Name__Address Street 1'][28] = 'orig_cred';
            $this->csv['Name__Address Street 1'][39] = 'service_dt';
            
            for($i = 0; $i < $this->rightShiftUpGroupAppends; $i++) {
                // HARD_CODED [7]-[13] are the fields being appended
                $headerMergeData = [
                    // $accountNumber, $this->rawDataHeaderRow[3] .
                    'acct_num' . ($i + 1),
                    // $origAccountNum, $this->rawDataHeaderRow[14]
                    'orig_acct' . ($i + 1),
                    // $balanceDue, $this->rawDataHeaderRow[17] .
                    'bal_due' . ($i + 1),
                    // $settlementAmount, $this->rawDataHeaderRow[19] .
                    'sett_amt' . ($i + 1),
                    // $origCreditor, $this->rawDataHeaderRow[28] .
                    'orig_cred' . ($i + 1),
                    // $dateOfService, $this->rawDataHeaderRow[39] .
                    'service_dt' . ($i + 1),
                ];
                
                $this->csv[$this->headerRowKey] = array_merge($this->csv[$this->headerRowKey], $headerMergeData);
                
                $break = "point";
            }
        }
        // if 35 we're merging every field except dupe identifiers
        if($this->numberOfFieldsToAppend === 35) {
            $copyRawDataHeaderRow = $this->rawDataHeaderRow;
            $neededHeaders = [];
            
            // WORST CASE = 35, just get the needed headers
            for($i = 0; $i < count($copyRawDataHeaderRow); $i++) {
                $item = $copyRawDataHeaderRow[$i];
                // manually skip indexes 5-10 "duplicate identifier fields"
                if($i === 5 || $i === 6 || $i === 7 || $i === 8 || $i === 9 || $i === 10) {
                    continue;
                }
                
                $neededHeaders [] = $item;
            }
            
            // start to dynamically create additional field titles
            for($a = 0; $a < $this->rightShiftUpGroupAppends; $a++) {
                $headerMergeData = [];
                // this is so I don't have to go through and type out all the hard coded values
                foreach($neededHeaders as $header) {
                    $headerMergeData [] = ($header . ' ' . ($a + 1));
                }
                
                $this->csv[$this->headerRowKey] = array_merge($this->csv[$this->headerRowKey], $headerMergeData);
                
                $break = "point";
            }
        }
        // if 8 we're merging only fields that change value per dupe rec
        else if($this->numberOfFieldsToAppend === 8) {
            for($i = 0; $i < $this->rightShiftUpGroupAppends; $i++) {
                // HARD_CODED [7]-[13] are the fields being appended
                $headerMergeData = [
                    // debtorAccount
                    $this->rawDataHeaderRow[2] . ' ' . ($i + 1),
                    // accountNumber
                    $this->rawDataHeaderRow[3] . ' ' . ($i + 1),
                    // origAccountNum
                    $this->rawDataHeaderRow[14] . ' ' . ($i + 1),
                    // principalBalance
                    $this->rawDataHeaderRow[15] . ' ' . ($i + 1),
                    // balanceDue
                    $this->rawDataHeaderRow[17] . ' ' . ($i + 1),
                    // settlementAmount
                    $this->rawDataHeaderRow[19] . ' ' . ($i + 1),
                    // dateService
                    $this->rawDataHeaderRow[39] . ' ' . ($i + 1),
                    // paymentDates
                    $this->rawDataHeaderRow[40] . ' ' . ($i + 1),
                ];
                
                $this->csv[$this->headerRowKey] = array_merge($this->csv[$this->headerRowKey], $headerMergeData);
                
                $break = "point";
            }
        }
    }
    
    // This hard codes the 6 fields Lindsey and I discussed
    private function hardCodeFields(array $data): array {
        // TODO: implement 'LIKE' algorithm for field titles !!!!!!!!!!!!!!!!!!
        // TODO: implement 'LIKE' algorithm, SERIOUSLY !!!!!!!!!!!!!!!!!!!!!!!! This is wayyyyyyyy too hard coded.
        $accountNumber = $data[3];          // KEEP THIS FIELD !! -D,  1
        $origAccountNum = $data[14];        // KEEP THIS FIELD !! -O,  2
        $balanceDue = $data[17];            // KEEP THIS FIELD !! -R,  3
        $settlementAmount = $data[19];      // KEEP THIS FIELD !! -T,  4
        $origCreditor = $data[28];          // KEEP THIS FIELD !! -AC, 5
        $dateOfService = $data[39];         // KEEP THIS FIELD !! -AN, 6
        
        
        return [
            $accountNumber,
            $origAccountNum,
            $balanceDue,
            $settlementAmount,
            $origCreditor,
            $dateOfService,
        ];
    }
    
    // This hard coded 35 fields given a data set with 41 total fields
    private function hardCodeFields1(array $data): array {
        // TODO: implement 'LIKE' algorithm for field titles !!!!!!!!!!!!!!!!!!
        // TODO: implement 'LIKE' algorithm, SERIOUSLY !!!!!!!!!!!!!!!!!!!!!!!! This is wayyyyyyyy too hard coded.
        $letterCode = $data[0];
        $debtorId = $data[1];
        // variable field value
        $debtorAccountId = $data[2];
        $accountNumber = $data[3];
        $addressId = $data[4];
        // index 5-10 are the "duplicate identifiers"
        $spouseName = $data[11];
        $clientId = $data[12];
        $clientName = $data[13];
        // variable field value
        $origAccountNum = $data[14];
        $principleBalance = $data[15];
        $chargesAssigned = $data[16];
        $balanceDue = $data[17];
        $settlementRate = $data[18];
        $settlementAmount = $data[19];
        $curCollector = $data[20];
        $earlySettleDate = $data[21];
        $lateSettleDate = $data[22];
        $letterDate = $data[23];
        $chargeOffDate = $data[24];
        $lastPayAmount = $data[25];
        $collectPhoneNum = $data[26];
        $remarks = $data[27];
        $origCreditor = $data[28];
        $totalBalance = $data[29];
        $totalPaid = $data[30];
        $collectorAlias = $data[31];
        $groupNumber = $data[32];
        $letterBalance = $data[33];
        $placementOwner = $data[34];
        $ssn = $data[35];
        $email = $data[36];
        $score = $data[37];
        $statueLimitations = $data[38];
        $dateOfService = $data[39];
        $paymentDates = $data[40];
        
        return [
            $letterCode,
            $debtorId,
            $debtorAccountId,
            $accountNumber,
            $addressId,
            $spouseName,
            $clientId,
            $clientName,
            $origAccountNum,
            $principleBalance,
            $chargesAssigned,
            $balanceDue,
            $settlementRate,
            $settlementAmount,
            $curCollector,
            $earlySettleDate,
            $lateSettleDate,
            $letterDate,
            $chargeOffDate,
            $lastPayAmount,
            $collectPhoneNum,
            $remarks,
            $origCreditor,
            $totalBalance,
            $totalPaid,
            $collectorAlias,
            $groupNumber,
            $letterBalance,
            $placementOwner,
            $ssn,
            $email,
            $score,
            $statueLimitations,
            $dateOfService,
            $paymentDates,
        ];
    }
}