<?php
declare(strict_types=1);

namespace Redstone\Tools;

use ParseCsv\Csv;
use Spatie\Regex\Regex;

abstract class RsmSuppressAbstract
{
    private $status;
    
    /**
     * This is the combined array of all the suppression CSVs
     *
     * @var array
     */
    protected $parseCsvSuppressData;
    
    /**
     * The base data. This data will have to get records removed based on
     * the suppression lists.
     *
     * @var Csv class
     */
    protected $parseCsvBaseData;
    
    protected $kAddress;
    protected $kCity;
    protected $kState;
    protected $kZip;
    
    /**
     * This zip will be the "left 5" of the zip field
     * I'll also need to check for 3 & 4 digit zips as well
     *
     * @var string
     */
    protected $kZip5;
    
    /**
     * $sk = suppress keys
     *
     * This is an example of how the $sk data structure should look with
     * realistic possible field titles
     *
     * This will get removed in the construction so it doesn't get
     * in the way when the program actually dynamically fills it
     *
     * @var array
     */
    protected $kSup = [
        [
            'address' => 'street address',
            'city' => 'prop_city',
            'state' => 'o state',
            'zip' => 'city_zipcode',
            'zip5' => 'LEFT(5, zip)', // CALCULATED value
        ],
    ];
    
    /**
     * DEVELOPMENT MODE property... for now.
     *
     * field to check for a 'contains' value, if it contains this
     * value the record that it exists in will be removed
     *
     * Eventually this will be what gets set in a web form
     *
     * @var string
     */
    protected $ignoreField = 'last_name';
    
    /**
     * A comma separated list of values to ignore, it'll become an array
     * and it'll get transformed to lower case
     *
     * @var string
     */
    protected $ignoreFieldContains = 'jones, smith, lopez';
    
    /**
     * @var array
     */
    protected $suppressedSet = [];
    
    /**
     * @var array
     */
    protected $recordsRemoved = [];
    
    /**
     * Add more to these feature sets as I study more input data
     * When dynamically finding these titles remove non alphanumerics
     *
     * These will get used in the "Dynamic" functions
     *
     * Right now if there is a tie / multiple matches I think
     * the lowest index will always win at the moment
     *
     * @var array
     */
    protected $featureSetAddress = [
        'address', 'addr', 'propaddr', 'streetaddress',
    ];
    protected $featureSetCity = [
        'city', 'ocity', 'propcity',
    ];
    protected $featureSetState = [
        'state', 'st', 'ostate', 'propstate',
    ];
    protected $featureSetZip = [
        'zip', 'zipcode', 'ozip', 'propzip',
    ];
    
    public function __construct() {
        array_shift($this->kSup);
        $this->status = 'RsmSuppressAbstract Ready';
    }
    
    protected function getStatus() {
        return $this->status;
    }
    
    /**
     * The child class for now will combine all the suppression files
     * It'll mutate abstract class property's
     *
     * @return mixed
     */
    abstract protected function suppressionCombine(): void;
    
    /**
     * Get the left 5 of the zip, if it's < 5 pad with 0's
     *
     * @param string $oZip
     *
     * @return string
     */
    private function zipExtract(string $oZip): string {
        $oZipLen = strlen($oZip);
        $coreFieldCombine = '';
        if($oZipLen >= 5) {
            $coreFieldCombine .= substr($oZip, 0, 5);
        }
        else if($oZipLen === 4) {
            $coreFieldCombine .= ('0' . $oZip);
        }
        else if($oZipLen === 3) {
            $coreFieldCombine .= ('00' . $oZip);
        }
        else {
            $coreFieldCombine .= $oZip;
        }
        return $coreFieldCombine;
    }
    
    /**
     * This function will search for literal [address], [city], [state]/[st], and [zip]
     */
    public function suppressionStart() {
        // function scoped properties
        $bestCase = ['address', 'city', 'state', 'st', 'zip'];
        $suppressionKeys = [];
        $wasBestCase = false; // assume worst case
        
        // $keys are lowercase, $keysOrig is their original case
        $getBestCase = function(array $keys, array $keysOrig) use (
            $bestCase, &$wasBestCase
        ): array {
            $results = [
                'best_case' => null, 'address' => null, 'city' => null,
                'state' => null, 'zip' => null,
            ];
            
            // set the [address], [city], [state]/[st], [zip] fields for base keys
            foreach($bestCase as $case) {
                if(in_array($case, $keys)) {
                    if('state' === $case || 'st' === $case) {
                        $idxState = array_search('state', $keys);
                        $idxSt = array_search('st', $keys);
                        
                        // I just need to know if it's "st" or "state"
                        if($idxState) {
                            $results['state'] = $keysOrig[$idxState];
                        }
                        else if($idxSt) {
                            $results['state'] = $keysOrig[$idxSt];
                        }
                        else {
                            $wasBestCase = false;
                            $results['best_case'] = $wasBestCase;
                            break;
                        }
                    }
                    
                    switch($case) {
                        case 'address':
                            $idxAddress = array_search('address', $keys);
                            $results['address'] = $keysOrig[$idxAddress];
                            $wasBestCase = true;
                            $results['best_case'] = $wasBestCase;
                            break;
                        case 'city':
                            $idxCity = array_search('city', $keys);
                            $results['city'] = $keysOrig[$idxCity];
                            $wasBestCase = true;
                            $results['best_case'] = $wasBestCase;
                            break;
                        case 'zip':
                            $idxZip = array_search('zip', $keys);
                            $results['zip'] = $keysOrig[$idxZip];
                            $wasBestCase = true;
                            $results['best_case'] = $wasBestCase;
                            break;
                    }
                }
            }
            
            if(in_array(null, $results)) {
                $wasBestCase = false;
                $results['best_case'] = $wasBestCase;
            }
            
            return $results;
        };
        
        /*
          get the keys from the base csv and the suppression lists
          just grab the keys at [0] because all N obs will have the same keys
        */
        
        // lower case all the base keys
        $origBaseKeys = array_keys($this->parseCsvBaseData->data[0]);
        $baseKeys = array_map(function($elem) {
            if(is_string($elem)) {
                return strtolower($elem);
            }
            // convert it to a string
            return (string)$elem;
        }, $origBaseKeys);
        
        // bc = best case i.e. best case results for header row
        $bcResults = $getBestCase($baseKeys, $origBaseKeys);
        $this->kAddress = $bcResults['address'];
        $this->kCity = $bcResults['city'];
        $this->kState = $bcResults['state'];
        $this->kZip = $bcResults['zip'];
        
        // get the suppression list keys, lowercase them and
        // set the [address], [city], [state]/[st], [zip] fields
        // this will also look for "best case"
        foreach($this->parseCsvSuppressData as $suppressionList) {
            $origSupKeys = array_keys($suppressionList->data[0]);
            $keys = array_map(function($elem) {
                if(is_string($elem)) {
                    return strtolower($elem);
                }
                return (string)$elem;
            }, $origSupKeys);
            
            $suppressionKeys[] = $keys;
            
            // best case results
            $bcResults = $getBestCase($keys, $origSupKeys);
            if(!$wasBestCase) break;
            $this->kSup[] = $bcResults;
        }
        
        if(!$wasBestCase) {
            // a simple [address], [city], [state]/[st], [zip] couldn't be found
            $this->suppressionStartDynamic();
        }
        else {
            $this->suppress();
        }
        
    } // END OF: suppressionStart()
    
    /**
     * This function will start to remove records from the base CSV that are in
     * the combined suppression CSVs
     *
     * It returns void because it mutates class properties
     *
     * It will also invoke createBaseHash() and createSuppressionHash()
     *
     * @return void
     */
    private function suppress(): void {
        $hashBaseArray = $this->createBaseHash();
        $hashSuppressionArray = $this->createSuppressionHash();
        $suppressedSet = [];
        $recordsRemoved = [];
        $C = 0;
        $headerRow = null;
        
        foreach($hashBaseArray as $key => $value) {
            if($C === 0) {
                // get header row real quick
                $headerRow = array_keys($value);
            }
            //****************************************
            //******** O(N+1) time complexity ********
            //****************************************
            $temp = $hashSuppressionArray[$key] ?? null;
            
            if($C % 2 === 0) {
                $formatC = number_format($C);
                ob_end_flush();
                $v = print_r($value, true);
                echo "\n__>> Scanned $formatC records, current record = $v\n";
                ob_start();
            }
            
            // if temp has a value this record needs to get suppressed
            if($temp === null) {
                $suppressedSet[$key] = $value;
            }
            else {
                $recordsRemoved[$key] = $value;
            }
            
            $C++;
        }
        
        // add the header row
        array_unshift($suppressedSet, $headerRow);
        array_unshift($recordsRemoved, $headerRow);
        
        $this->suppressedSet = $suppressedSet;
        $this->recordsRemoved = $recordsRemoved;
        
    } // END OF: suppress()
    
    /**
     * This function will create a hash by combining address, city, state, zip fields
     * and removing all the non alphanumeric chars
     *
     * The hash is created for the 1 base csv file
     *
     * @return array
     */
    private function createBaseHash(): array {
        // function scoped properties, the hash arrays will probably
        // be what get exported as the suppressed CSV
        $hashBaseArray = [];
        $alphaNumPattern = '/[^0-9a-zA-Z]/';
        
        // loop over the 1D base csv array
        // create a hash to suppress on by getting rid of all alphanumeric chars
        foreach($this->parseCsvBaseData->data as $item) {
            // 1117 s 9th st San Jose, ca 95112
            // address + city + state + zip
            $coreFieldCombine = $item[$this->kAddress];
            $coreFieldCombine .= $item[$this->kCity];
            $coreFieldCombine .= $item[$this->kState];
            
            // the zip is special, it can be [95112, 7001, 95813-1111, 512]
            // if it's < 5 digits it's because excel truncates leading 0's
            $oZip = $item[$this->kZip];
            $coreFieldCombine .= $this->zipExtract($oZip);
            
            // Perhaps refactor this to a lambda to upload dry rule
            $coreFieldsRegex = Regex::match($alphaNumPattern, $coreFieldCombine);
            if($coreFieldsRegex->hasMatch()) {
                // now get rid of all non alphanumeric chars
                $hash = Regex::replace($alphaNumPattern, '', $coreFieldCombine)->result();
                $hashBaseArray[$hash] = $item;
            }
            else {
                // maybe throw an exception
                $break = 'point';
            }
        }
        
        return $hashBaseArray;
    }
    
    /**
     * This function will create a hash by combining address, city, state, zip fields
     * and removing all the non alphanumeric chars
     *
     * The hash combines N suppression csv files
     *
     * @return array
     */
    private function createSuppressionHash(): array {
        $hashSuppressionArray = [];
        $alphaNumPattern = '/[^0-9a-zA-Z]/';
        
        // loop over the suppression 2D array and create a hash
        // The 1 to N sets will be transformed to a 1D array
        $c = 0;
        foreach($this->parseCsvSuppressData as $file) {
            $data = $file->data;
            
            for($i = 0; $i < count($data); $i++) {
                $item = $data[$i];
                $_address = $this->kSup[$c]['address'];
                $_city = $this->kSup[$c]['city'];
                $_state = $this->kSup[$c]['state'];
                $_zip = $this->kSup[$c]['zip'];
                
                // 1117 s 9th st San Jose, ca 95112
                // address + city + state + zip
                $coreFieldCombine = $item[$_address];
                $coreFieldCombine .= $item[$_city];
                $coreFieldCombine .= $item[$_state];
                
                // the zip is special, it can be [95112, 7001, 95813-1111, 512]
                // if it's < 5 digits it's because excel truncates leading 0's
                $oZip = $item[$_zip];
                $coreFieldCombine .= $this->zipExtract($oZip);
                
                
                // Perhaps refactor this to a lambda to uphold dry rule
                $coreFieldsRegex = Regex::match($alphaNumPattern, $coreFieldCombine);
                if($coreFieldsRegex->hasMatch()) {
                    // now get rid of all non alphanumeric chars
                    $hash = Regex::replace($alphaNumPattern, '', $coreFieldCombine)->result();
                    $hashSuppressionArray[$hash] = $item; // varies here
                }
            }
            
            $c++;
        }
        
        return $hashSuppressionArray;
    }
    
    /**
     * This function is trying to dynamically figure out what the
     * address, city, st, and zip fields are using the feature sets
     *
     * Because we don't know how many suppression lists there will be I will
     * scan each array in the collection and figure it out
     */
    private function suppressionStartDynamic() {
        // idx array of field titles
        $currentKeys = $this->parseCsvBaseData->titles;
        
        // match all the core fields to the feature set
        $lambdaMatch = function(array $currentKeys, array $featureSet): array {
            return array_filter($currentKeys, function($val, $key) use ($featureSet) {
                $alphaNum = '/[^0-9a-zA-Z]/';
                
                if(Regex::match($alphaNum, $val)->hasMatch()) {
                    $tempVal = Regex::replace($alphaNum, '', $val)->result();
                    $tempVal = strtolower($tempVal);
                    $addressKey = array_search($tempVal, $featureSet);
                    if($addressKey !== false) {
                        return true;
                    }
                }
                else {
                    $tempVal = strtolower($val);
                    $addressKey = array_search($tempVal, $featureSet);
                    if($addressKey !== false) {
                        return true;
                    }
                }
                
                return false;
            }, ARRAY_FILTER_USE_BOTH); // end of array_filter()
        };
        
        // mutate the ref $mapKey with the $lambdaMatch() match
        $lambdaMapKey = function(
            ?string &$address, ?string &$city, ?string &$state, ?string &$zip, array $currentKeys
        ) use ($lambdaMatch): void {
            
            /* I have to use array_values() because array_filter() preserves the keys */
            
            // get all the [address] matches just in case there is more than 1 match
            $activeAddress = array_values($lambdaMatch($currentKeys, $this->featureSetAddress));
            if(count($activeAddress) === 1) {
                $address = $activeAddress[0];
            }
            else {
                echo "<br>|| There was more than 1 [address] match<br>";
                //TODO: implement a "tie breaker" algorithm when there is more than 1 match
                $address = $activeAddress[0];
            }
            
            // get all the [city] matches just in case there is more than 1 match
            $activeCity = array_values($lambdaMatch($currentKeys, $this->featureSetCity));
            if(count($activeCity) === 1) {
                $city = $activeCity[0];
            }
            else {
                echo "<br>|| There was more than 1 [city] match<br>";
                //TODO: implement a "tie breaker" algorithm when there is more than 1 match
                $city = $activeCity[0];
            }
            
            // get all the [state] matches just in case there is more than 1 match
            $activeState = array_values($lambdaMatch($currentKeys, $this->featureSetState));
            if(count($activeState) === 1) {
                $state = $activeState[0];
            }
            else {
                echo "<br>|| There was more than 1 [state] match <br>";
                //TODO: implement a "tie breaker" algorithm when there is more than 1 match
                $state = $activeState[0];
            }
            
            // get all the [zip] matches just in case there is more than 1 match
            $activeZip = array_values($lambdaMatch($currentKeys, $this->featureSetZip));
            if(count($activeZip) === 1) {
                $zip = $activeZip[0];
            }
            else {
                echo "<br>|| There was more than 1 [zip] match <br>";
                //TODO: implement a "tie breaker" algorithm when there is more than 1 match
                $zip = $activeZip[0];
            }
        };
        
        // BASE SET
        $lambdaMapKey(
            $this->kAddress,
            $this->kCity,
            $this->kState,
            $this->kZip,
            $currentKeys
        );
        
        // SUPPRESSION SET, loop over suppression files, check if best_case was set
        $c = 0;
        // move the "pointer" till it reaches a record that was not 'best_case'
        while(isset($this->kSup[$c]) && $this->kSup[$c]['best_case'] === true) {
            $c++;
        }
        // start at the point where there was not a 'best_case' via $i = $c
        for($i = $c; $i < count($this->parseCsvSuppressData); $i++){
            $item = $this->parseCsvSuppressData[$i];
            $currentKeys = $item->titles;
            $this->kSup[] = ['best_case' => false];
            $curIdx = (count($this->kSup)-1);
            $rec = &$this->kSup[$curIdx];
            $rec['address'] = null;
            $rec['city'] = null;
            $rec['state'] = null;
            $rec['zip'] = null;
    
            $address = &$rec['address'];
            $city = &$rec['city'];
            $state = &$rec['state'];
            $zip = &$rec['zip'];
            $lambdaMapKey(
                $address, $city, $state,
                $zip, $currentKeys
            );
        }
        
        // Now that the base and suppression header fields have been mapped,
        // suppress() the base file
        $this->suppress();
        
    } // END OF: suppressionStartDynamic()
}