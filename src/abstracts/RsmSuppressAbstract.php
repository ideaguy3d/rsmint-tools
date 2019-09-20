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
            'zip' => 'city-zip',
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
                'address' => null,
                'city' => null,
                'state' => null,
                'zip' => null,
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
        
        // best case results
        $bcResults = $getBestCase($baseKeys, $origBaseKeys);
        $this->kAddress = $bcResults['address'];
        $this->kCity = $bcResults['city'];
        $this->kState = $bcResults['state'];
        $this->kZip = $bcResults['zip'];
        
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
        
        $break = 'point';
        
        if(!$wasBestCase) {
            // a simple [address], [city], [state]/[st], [zip] couldn't be found
            $this->suppressionStartDynamic();
        }
        else {
            $this->suppress();
        }
    }
    
    /**
     * This function will create a hash by combining address, city, state, zip fields
     * and removing all the non alphanumeric chars
     */
    public function suppress() {
        // function scoped properties, the hash arrays will probably
        // be what get exported as the suppressed CSV
        $baseHashArray = [];
        $suppressionHashArray = [];
        $alphaNumPattern = '/[^0-9a-zA-Z]/';
        
        // create a hash to suppress on by getting rid of all alphanumeric chars
        foreach($this->parseCsvBaseData->data as $item) {
            // 1117 s 9th st San Jose, ca 95112
            // address + city + state + zip
            $coreFieldCombine = $item[$this->kAddress];
            $coreFieldCombine .= $item[$this->kCity];
            $coreFieldCombine .= $item[$this->kState];
            $coreFieldCombine .= $item[$this->kZip];
            
            // Perhaps refactor this to a lambda to upload dry rule
            $coreFieldsRegex = Regex::match($alphaNumPattern, $coreFieldCombine);
            if($coreFieldsRegex->hasMatch()) {
                // now get rid of all non alphanumeric chars
                $hash = Regex::replace($alphaNumPattern, '', $coreFieldCombine)->result();
                $baseHashArray[$hash] = $item;
            }
            else {
                // maybe throw an exception
                $break = 'point';
            }
        }
        
        foreach($this->parseCsvSuppressData as $file) {
            $data = $file->data;
            for($i = 0; $i < count($data); $i++) {
                $item = $data[$i];
                // 1117 s 9th st San Jose, ca 95112
                // address + city + state + zip
                $coreFieldCombine = $item[$this->kSup[$i]['address']];
                $coreFieldCombine .= $item[$this->kSup[$i]['city']];
                $coreFieldCombine .= $item[$this->kSup[$i]['state']];
                $coreFieldCombine .= $item[$this->kSup[$i]['zip']];
                
                // Perhaps refactor this to a lambda to upload dry rule
                $coreFieldsRegex = Regex::match($alphaNumPattern, $coreFieldCombine);
                if($coreFieldsRegex->hasMatch()) {
                    // now get rid of all non alphanumeric chars
                    $hash = Regex::replace($alphaNumPattern, '', $coreFieldCombine)->result();
                    $suppressionHashArray[$hash] = $item; // varies here
                }
            }
        }
        
        $break = 'point';
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
    }
}