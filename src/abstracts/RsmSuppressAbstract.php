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
     * @var Csv
     */
    protected $parseCsvBaseData;
    
    protected $address;
    protected $city;
    protected $state;
    protected $zip;
    
    /**
     * Add more to these feature sets as I study more input data
     * When dynamically finding these titles remove non alphanumerics
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
        
        // get the keys from the base csv and the suppression lists
        // just grab the keys at [0] because all N obs will have the same keys
        $baseKeys = array_keys($this->parseCsvBaseData->data[0]);
        // lower case all the keys
        $baseKeys = array_map(function($elem) {
            if(is_string($elem)) {
                return strtolower($elem);
            }
            return $elem;
        }, $baseKeys);
        
        foreach($this->parseCsvSuppressData as $suppressionList) {
            $suppressionKeys[] = $suppressionList->data[0];
        }
        
        foreach($bestCase as $case) {
            if(in_array($case, $baseKeys)) {
                if($case = 'state' || $case = 'st') {
                    // I just need to know if it's "st" or "state"
                    $stateIdx = array_search('state', $baseKeys);
                    if(!$stateIdx) $this->state = 'state';
                    else $this->state = 'st';
                }
                $this->address = 'address';
                $this->city = 'city';
                $this->zip = 'zip';
            }
        }
        
        $break = 'point';
        $this->suppressionStartDynamic();
    }
    
    /**
     * This function is trying to dynamically figure out what the
     * address, city, st, and zips fields are
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