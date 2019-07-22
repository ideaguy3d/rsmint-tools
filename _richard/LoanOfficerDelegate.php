<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 8/28/2018
 * Time: 4:56 PM
 */

// For client "Pacific Home Loans"
class LoanOfficerDelegate
{
    private $loanOfficerArr;
    private $dataArr;
    private $rawDataFile;
    private $loanOfficerFile;
    private $exportFolder = 'C:\Users\julius\Desktop\ninja';
    private $appendBy;
    
    //TODO: check for misspelled words and make better use of strpos() for core wording e.g. any field that contains a phone number should always have "number" or "phone" somewhere in the field title name
    // possible field titles
    private $loStateTitles = ['st', 'state', 'loan_officer_state'];
    // possible field titles
    private $loCountTitles = ['counts', 'loan_officer_counts', 'state_counts'];
    /**
     * These next class fields are column titles in the $loadOfficerArr
     * that are going to be used to uniquely identify loan officer info and or records
     * This is sort of kind of like the SQL "LIKE" reserved keyword
     */
    // Super Noob "machine learning feature set"
    // possible field titles for the "Loan Officers' name"
    private $loColumnTitles = ['1st_drop_date', 'loan_officer', 'officer_name', 'name', 'officer'];
    // possible field titles for the "Loan Officers' numbers"
    private $loPhoneNumberTitles = ['phone_numbers', 'number', 'numbers', 'phone'];
    
    //TODO: try to use a generator instead
    public function __construct(string $loanOfficerPath, string $dataPath) {
        $this->loanOfficerArr = [];
        $this->dataArr = [];
        $count = 0;
        
        $this->loanOfficerFile = glob($loanOfficerPath . '\*.csv', GLOB_NOCHECK)[0];
        $this->rawDataFile = glob($dataPath . '\*.csv', GLOB_NOCHECK)[0];
        
        //-- create the $loanOfficerArray from CSV:
        if(($loanOfficerHandle = fopen($this->loanOfficerFile, 'r')) !== false) {
            while(($loanOfficerData = fgetcsv($loanOfficerHandle, 8096, ",")) !== false) {
                $this->loanOfficerArr[$count] = $loanOfficerData;
                $count++;
            }
            
            fclose($loanOfficerHandle);
        }
        
        $count = 0;
        
        //-- create $dataArr from CSV:
        if(($dataHandle = fopen($this->rawDataFile, 'r')) !== false) {
            while(($dataData = fgetcsv($dataHandle, 8096, ",")) !== false) {
                $this->dataArr[$count] = $dataData;
                $count++;
            }
            fclose($dataHandle);
        }
    }
    
    //-----------------------------
    // - Main container function -
    //-----------------------------
    public function runLoanOfficerDelegate(): void {
        $loanOfficerInfo = $this->createLoanOfficerInfoArr();
        $this->rawDataIntegrate($loanOfficerInfo);
        $this->export2csv();
    }
    
    /**
     * This function won't assign "by count", it will just assign agents according
     * to the county they get, e.g. if jane doe has "Santa Clara County" assigned to
     * her she gets appended to all the SCC records in raw data
     *
     *  @param
     */
    private function assignNotByCount() {
    
    }
    
    /**
     * This function will assign loan officers to states by count according
     * to the [count] field in the "LoanOfficerInfo" CSV file
     *
     * @return array - the array of loan officers with an assigned count
     */
    //TODO: refactor this function into several smaller functions to write better unit tests
    private function createLoanOfficerInfoArr(): array {
        $loNameIndex = null; // index of loan officers' name
        $loNumberIndex = null; // index of loan officers' number
        $loStateIndex = null; // index of state
        $loCountIndex = null; // index of the count per state
        
        $loanOfficersNew = [];
        // create a way to uniquely identify loan officers
        $titles = $this->loanOfficerArr[0];
        // normalize titles
        $titles = array_map(function($e) {
            // Change: "  Some  titLe   like thIs " to "some_title_like_this"
            return preg_replace("/\s/", "_", trim(strtolower($e)));
        }, $titles);
        
        //TODO: try to refactor this to do a recursive search instead
        // Do an iterative search over header row for the indexes of The Loan Officer
        // 1) Name,  2) Number, 3) State, 4) Counts
        // by checking to see if column title is in "$this->loColumnTitles"
        for($i = 0; $i < count($titles); $i++) {
            $item = $titles[$i];
            // is this field probably the loan officers name?
            if(in_array($item, $this->loColumnTitles)) {
                $loNameIndex = $i; // this Assumes an indexed array...
                if($loNumberIndex && $loStateIndex && $loCountIndex) break; // found everything, stop loop
            }
            // is this field probably the loan officers number?
            else if(in_array($item, $this->loPhoneNumberTitles)) {
                $loNumberIndex = $i;
                if($loNameIndex & $loStateIndex && $loCountIndex) break; // found everything, stop loop
            }
            else if(in_array($item, $this->loStateTitles)) {
                $loStateIndex = $i;
                if($loNumberIndex && $loNameIndex && $loCountIndex) break; // found everything, stop loop
            }
            else if(in_array($item, $this->loCountTitles)) {
                $loCountIndex = $i;
                if($loNameIndex && $loNumberIndex && $loStateIndex) break;
            }
        }
        
        // NOTE: this is NOT depending on Column Order, it dynamically finds the correct idx
        if(in_array($titles[$loNameIndex], $this->loColumnTitles)) {
            $tempArr = $this->loanOfficerArr;
            array_shift($tempArr);
            
            $loanOfficers = array_column($tempArr, $loNameIndex);
            $loanOfficersNumber = array_column($tempArr, $loNumberIndex);
            $loanOfficers = array_map(function($e) { return trim($e); }, $loanOfficers);
            $loanOfficersNumber = array_map(function($e) { return trim($e); }, $loanOfficersNumber);
            $loanOfficers = array_unique($loanOfficers);
            $loanOfficersNumber = array_unique($loanOfficersNumber);
            
            //TODO: thoroughly check this, THIS MAY BE A FLAW with weird edge cases
            // $loanOfficers and $loanOfficersNumber have same number of elements & the same indexes
            foreach($loanOfficersNumber as $i => $value) {
                $itemNumber = $loanOfficersNumber[$i];
                $itemName = $loanOfficers[$i];
                $loanOfficers[$i] = $itemName . "_" . $itemNumber;
            }
            
            if(RSM_DEBUG_MODE) {
                echo "\n\rbreakpoint\n\r";
            }
            
            // At this point $loanOfficers = [2 => 'foo', 7 => 'bar', 26 => 'baz']
            // so transform arr keys to be loan officer name
            $loanOfficersNew = array_flip($loanOfficers);
            // now make key same as name
            foreach($loanOfficers as $key => $value) {
                $loanOfficersNew[$value] = ['id' => $value];
            }
            
            // now iterate over the 30+ recs (from orig "loan officer info" csv file)
            // start $i = 1 because 0 are column titles
            for($i = 1; $i < count($this->loanOfficerArr); $i++) {
                $item = $this->loanOfficerArr[$i]; // $item will be a row
                //TODO: Figure out how to NOT do this inner loop >:\
                foreach($loanOfficersNew as $key => $value) {
                    $name = strstr($value['id'], "_", true);
                    $number = str_replace("_", "", strstr($value['id'], "_"));
                    if(
                        (strpos($item[$loNameIndex], $name) !== false) &&
                        (strpos($item[$loNumberIndex], $number) !== false)
                    ) {
                        $loanOfficersNew[$value['id']] [] = ['state' => $item[$loStateIndex],
                            'count' => (int)$item[$loCountIndex],
                            'currentCount' => 0, // prep it for integration with actual raw data file
                        ];
                    }
                    if(RSM_DEBUG_MODE) {
                        echo "\n\rbreakpoint\n\r";
                    }
                }
            }
            if(RSM_DEBUG_MODE) {
                echo "\n\rbreakpoint\n\r";
            }
        }
        
        return $loanOfficersNew;
    }
    
    // This is "HARDCODED" - it depends on a order column to be correct
    private function rawDataIntegrate(array $loanOfficerInfo): void {
        // add a new field to header row
        $this->dataArr[0][count($this->dataArr[0])] = 'loan_officer';
        
        // ----------- OUTER loop -----------
        foreach($loanOfficerInfo as $key => $value) {
            // ----------- 1st inner loop -----------
            // loop over the 30,000+ records from raw data
            for($row = 1; $row < count($this->dataArr); $row++) {
                $record = $this->dataArr[$row];
                $headerRowSize = count($this->dataArr[0]);
                //===================================
                // << HARD_CODED >> 5 = state column
                //===================================
                $rdState = $record[5]; // rd = raw data
                $rdLoanOfficer = isset($this->dataArr[$row][$headerRowSize])
                    ? $this->dataArr[$row][$headerRowSize] : null;
                
                // ----------- 2nd inner loop -----------
                // loop over the loan officers' custom data structure
                // ... minus 1 because one of the keys isn't an index
                for($i = 0; $i < (count($loanOfficerInfo[$key]) - 1); $i++) {
                    $loState = $loanOfficerInfo[$key][$i]['state'];
                    $hitMaxCount = $loanOfficerInfo[$key][$i]['currentCount']
                        >= $loanOfficerInfo[$key][$i]['count'];
                    if(($loState === $rdState) && empty($rdLoanOfficer) && !$hitMaxCount) {
                        $this->dataArr[$row][$headerRowSize] = strstr($key, "_", true);
                        $loanOfficerInfo[$key][$i]['currentCount']++;
                        if(RSM_DEBUG_MODE) {
                            echo "\nbreakpoint - LoanOfficerDelegate.php line 202, looping over loan officers' data structure\n";
                        }
                    }
                }
                
            } // END OF for() loop
            
            if(RSM_DEBUG_MODE) {
                echo "\nbreakpoint - LoanOfficerDelegate.php line 210, transitioning to next loanOfficerInfo key\n";
            }
        } // END OF foreach() loop
    }
    
    // Delete files in folders AND export dataArr to CSV
    private function export2csv(): void {
        $path = $this->exportFolder . '\\completed_' . basename($this->rawDataFile);
        if(($handle = fopen($path, 'w')) !== false) {
            foreach($this->dataArr as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }
        echo "<h1>File located at </h1><p><code>{$path}</code></p>";
        unlink($this->rawDataFile);
        unlink($this->loanOfficerFile);
    }
}