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
        
        $getBestCase = function(array $keys) use ($bestCase, &$wasBestCase): array {
            $results = [
                'address' => null,
                'city' => null,
                'state' => null,
                'zip' => null
            ];
            
            // set the [address], [city], [state]/[st], [zip] fields for base keys
            foreach($bestCase as $case) {
                if(in_array($case, $keys)) {
                    if('state' === $case || 'st' === $case) {
                        // I just need to know if it's "st" or "state"
                        if(array_search('state', $keys)) {
                            $results['state'] = 'state';
                        }
                        else if(array_search('st', $keys)) {
                            $results['state'] = 'st';
                        }
                        else {
                            $wasBestCase = false;
                            break;
                        }
                    }
                    $results['address'] = 'address';
                    $results['city'] = 'city';
                    $results['zip'] = 'zip';
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
        $baseKeys = array_map(function($elem) {
            if(is_string($elem)) {
                return strtolower($elem);
            }
            // convert it to a string
            return (string)$elem;
        }, array_keys($this->parseCsvBaseData->data[0]));
    
        // best case results
        $bcResults = $getBestCase($baseKeys);
        $this->kAddress = $bcResults['address'];
        $this->kCity = $bcResults['city'];
        $this->kState = $bcResults['state'];
        $this->kZip = $bcResults['zip'];
        
        // get the suppression list keys, lowercase them and
        // set the [address], [city], [state]/[st], [zip] fields
        foreach($this->parseCsvSuppressData as $suppressionList) {
            $keys = array_map(function($elem) {
                if(is_string($elem)) {
                    return strtolower($elem);
                }
                return (string)$elem;
            }, array_keys($suppressionList->data[0]));
            
            $suppressionKeys[] = $keys;
            
            $bcResults = $getBestCase($keys);
            $this->kSup[] = $bcResults;
        }
        
        $break = 'point';
        
        if(!$wasBestCase) {
            // a simple [address], [city], [state]/[st], [zip] couldn't be found
            $this->suppressionStartDynamic();
        }
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