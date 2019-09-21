<?php
declare(strict_types=1);

namespace Redstone\Tools;

use mysql_xdevapi\Exception;
use ParseCsv\Csv;
use Spatie\Regex\Regex;

abstract class RsmSuppressAbstract
{
    private $status;
    
    /**
     * This data will use the ParseCsv library
     *
     * @var Csv
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
            'zip5' => 'city_zipcode', // CALCULATED value
        ],
    ];
    
    /**
     * DEVELOPMENT MODE property
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
     * it'll get transformed to lower case
     *
     * @var string
     */
    protected $contains = 'jones, smith, lopez';
    
    /**
     * Add more to these feature sets as I study more input data
     * When dynamically finding these titles remove non alphanumerics
     *
     * These will get used in the "Dynamic" functions
     *
     * @var array
     */
    protected $addressFeatureSet = [
        'address', 'addr', 'propadr', 'streetaddress',
    ];
    protected $cityFeatureSet = ['city', 'ocity', 'propcity'];
    protected $stateFeatureSet = ['state', 'ostate', 'propstate'];
    protected $zipFeatureSet = ['zip', 'ozip', 'propzip'];
    
    public function __construct() {
        array_shift($this->kSup);
        $this->status = 'RsmSuppressAbstract Ready';
    }
    
    protected function getStatus() {
        return $this->status;
    }
    
    abstract protected function suppressionCombine();
    
    /**
     * Get the left 5 of the zip, if it's < 5 pad with 0's
     *
     * @param string $oZip
     *
     * @return string
     */
    protected function zipExtract(string $oZip): string {
        $oZipLen = strlen($oZip);
        $coreFieldCombine = '';
        if($oZipLen >= 5) {
            $coreFieldCombine .= substr($oZip, 0, 5);
        }
        else if($oZipLen === 4) {
            $coreFieldCombine .= ('0' . $oZip);
        }
        else if($oZipLen === 3){
            $coreFieldCombine .= ('00' . $oZip);
        }
        else {
            $coreFieldCombine .= $oZip;
        }
        return $coreFieldCombine;
    }
    
    /**
     * This function will such for literal [address], [city], [state]/[st], and [zip]
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
                'address' => null, 'city' => null,
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
                            break;
                        }
                    }
                    $idxAddress = array_search('address', $keys);
                    $idxCity = array_search('city', $keys);
                    $idxZip = array_search('zip', $keys);
                    
                    $results['address'] = $keysOrig[$idxAddress];
                    $results['city'] = $keysOrig[$idxCity];
                    $results['zip'] = $keysOrig[$idxZip];
                    $wasBestCase = true;
                }
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
        
        // best case results for header row
        $bcResults = $getBestCase($baseKeys, $origBaseKeys);
        $this->kAddress = $bcResults['address'];
        $this->kCity = $bcResults['city'];
        $this->kState = $bcResults['state'];
        $this->kZip = $bcResults['zip'];
        $this->kZip5 = '';
        
        // get the suppression list keys, lowercase them and
        // set the [address], [city], [state]/[st], [zip] fields
        foreach($this->parseCsvSuppressData as $suppressionList) {
            $origSupKeys = array_keys($suppressionList->data[0]);
            $keys = array_map(function($elem) {
                if(is_string($elem)) {
                    return strtolower($elem);
                }
                return (string)$elem;
            }, $origSupKeys);
            
            $suppressionKeys[] = $keys;
            
            $bcResults = $getBestCase($keys, $origSupKeys);
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
     * @return void
     */
    public function suppress(): void {
        $hashBaseArray = $this->createBaseHash();
        $hashSuppressionArray = $this->createSuppressionHash();
        $suppressedSet = [];
        $recordsRemoved = [];
        $C = 0;
    
        foreach($hashBaseArray as $key => $value) {
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
        
        $break = 'point';
    } // END OF: suppress()
    
    /**
     * This function will create a hash by combining address, city, state, zip fields
     * and removing all the non alphanumeric chars
     *
     * The hash is created for the 1 base csv file
     *
     * @return array
     */
    public function createBaseHash(): array {
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
    public function createSuppressionHash(): array {
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
     * address, city, st, and zip fields are
     *
     * Because we don't know how many suppression lists there will be I will
     * scan each array in the collection and figure it out
     */
    public function suppressionStartDynamic() {
        // _SUPER HARDCODED purely for testing/development purposes
        $currentKeys = array_keys($this->parseCsvSuppressData[1]->data[2]);
        $activeAddress = array_filter($currentKeys, function($key, $val) {
            $alphaNum = '/[^0-9a-zA-Z]/';
            
            if(Regex::match($alphaNum, $key)->hasMatch()) {
                $tempVal = Regex::replace($alphaNum, '', $key)->result();
                $tempVal = strtolower($tempVal);
                if($addressKey = array_search($tempVal, $this->addressFeatureSet)) {
                    return true;
                }
            }
            
            return false;
            
        }, ARRAY_FILTER_USE_BOTH);
        
        $break = 'point';
    } // END OF: suppressionStartDynamic()
}