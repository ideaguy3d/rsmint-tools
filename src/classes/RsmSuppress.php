<?php
declare(strict_types=1);

namespace Redstone\Tools;


use ParseCsv\Csv;

class RsmSuppress extends RsmSuppressAbstract
{
    public $status;
    
    private $path2dataFolder;
    private $path2suppressionFolder;
    private $baseFile;
    private $baseFileFullPath;
    
    /**
     * @var array
     */
    private $suppressionFiles = [];
    
    /**
     * The constructor will scan the csv files, and convert them all to in-memory objects
     *
     * @param string $baseFile
     * @param array $suppressionFiles
     * @param $log
     *
     * RsmSuppress constructor.
     */
    public function __construct(?string $baseFile, array $suppressionFiles, $log) {
        parent::__construct();
        $this->log = $log;
        $this->status = 'RsmSuppress ready';
        $this->baseFile = $baseFile;
        foreach($suppressionFiles as $suppressionFile) {
            $this->suppressionFiles[] = $suppressionFile;
        }
        
        // this may not be the lit
        $this->suppressId = substr($baseFile, 0, 8);
        $folder = AppGlobals::PathToUploadDirectory();
        $this->baseFileFullPath = $folder . DIRECTORY_SEPARATOR . $baseFile;
        $this->exportPath = $folder;
        
        // _HARD CODED to my flash drive location
        // flash drive: E:\redstone\uploads\77542\data, E:\redstone\uploads\77542\suppress
        // localhost: C:\xampp\htdocs\redstone\uploads\77542\data, C:\xampp\htdocs\redstone\uploads\77542\suppress
        $this->path2dataFolder = "$folder\data";
        $this->path2suppressionFolder = "$folder\suppress";
        
        $this->readFiles(); // this may be verbose
    }
    
    public function getStatus(): string {
        return parent::getStatus() . ' > ' . $this->status;
    }
    
    public function readFiles(): void {
        $this->log->info(" | base file full path = {$this->exportPath} | ");
        $this->parseCsvBaseData = new Csv($this->baseFileFullPath);
        $this->suppressionCombine(); // this may also be verbose
    }
    
    public function suppressionCombine(): void {
        /*
        $suppressionFiles = scandir($this->path2suppressionFolder);
        array_shift($suppressionFiles);
        array_shift($suppressionFiles);
        */
        
        $this->log->info("| export path =  {$this->exportPath}|");
        
        foreach($this->suppressionFiles as $suppressionFile) {
            $this->parseCsvSuppressData[] = new Csv(
                $this->exportPath . DIRECTORY_SEPARATOR . $suppressionFile
            );
        }
    }
    
    /**
     * @return array
     */
    public function getRecordsRemoved(): array {
        return $this->recordsRemoved;
    }
    
    /**
     * @return array
     */
    public function getSuppressedSet(): array {
        return $this->suppressedSet;
    }
    
} // END OF: RsmSuppress