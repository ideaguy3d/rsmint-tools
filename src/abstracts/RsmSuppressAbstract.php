<?php
declare(strict_types=1);

namespace Redstone\Tools;

use ParseCsv\Csv;

abstract class RsmSuppressAbstract
{
    private $status;

    protected $suppressData;

    /**
     * This data will use the ParseCsv library
     *
     * @var Csv
     */
    protected $parseCsvSuppressData;

    /**
     * Add more to these feature sets as I study more input data
     * When dynamically finding these titles remove non alphanumerics
     *
     * @var array
     */
    protected $addressFeatureSet = ['address', 'addr', 'propadr'];
    protected $cityFeatureSet = ['city', 'ocity', 'propcity'];
    protected $stateFeatureSet = ['state', 'ostate', 'propstate'];
    protected $zipFeatureSet = ['zip', 'ozip', 'propzip'];

    /**
     * The base data. This data will have to get records removed based on
     * the suppression lists.
     *
     * @var Csv
     */
    protected $parseCsvBaseData;
    
    public function __construct() {
        $this->status = 'RsmSuppressAbstract Ready';
    }
    
    protected function getStatus() {
        return $this->status;
    }
    
    abstract protected function suppressionCombine();

    public function suppressionStart() {

    }
}