<?php
declare(strict_types=1);

namespace Redstone\Tools;


use ParseCsv\Csv;

class RsmSuppress extends RsmSuppressAbstract
{
    public $status;
    private $path2dataFolder;
    private $path2suppressionFolder;
    
    /**
     * The constructor will scan the csv files, and convert them all to in-memory objects
     *
     * RsmSuppress constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->status = 'RsmSuppress ready';
        
        //TODO: dynamically check the host to determine local or pro env
        
        // _HARD CODED to my flash drive location
        // flash drive: 'E:\redstone\uploads\77542\data', 'E:\redstone\uploads\77542\suppress'
        // localhost:
        $this->path2dataFolder = 'C:\xampp\htdocs\redstone\uploads\77542\data';
        $this->path2suppressionFolder = 'C:\xampp\htdocs\redstone\uploads\77542\suppress';
        $this->readFiles(); // this may be verbose
    }
    
    public function getStatus() {
        return parent::getStatus() . ' > ' . $this->status;
    }
    
    public function readFiles() {
        $this->parseCsvBaseData = new Csv($this->path2dataFolder . '\data.csv');
        $this->suppressionCombine(); // this may also be verbose
    }
    
    public function suppressionCombine() {
        $suppressionFiles = scandir($this->path2suppressionFolder);
        array_shift($suppressionFiles);
        array_shift($suppressionFiles);
        
        foreach($suppressionFiles as $suppressionFile) {
            $this->parseCsvSuppressData[] = new Csv(
                $this->path2suppressionFolder . DIRECTORY_SEPARATOR . $suppressionFile
            );
        }
        
        $break = 'point';
    }
    
} // END OF: RsmSuppress