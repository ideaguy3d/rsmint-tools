<?php
declare(strict_types=1);

namespace Redstone\Tools;


use ParseCsv\Csv;

class RsmSuppress extends RsmSuppressAbstract
{
    public $status;
    private $path2dataFolder;
    private $path2suppressionFolder;

    public function __construct() {
        parent::__construct();
        $this->status = 'RsmSuppress ready';

        //TODO: dynamically check the host to determine local or pro env

        // _HARD CODED to my flash drive location
        $this->path2dataFolder = 'E:\redstone\uploads\77542\data';
        $this->path2suppressionFolder = 'E:\redstone\uploads\77542\suppress';
        $this->readFiles(); // this may be verbose
    }

    public function getStatus() {
        return parent::getStatus() . ' > ' . $this->status;
    }

    public function readFiles() {
        $this->parseCsvBaseData = new Csv($this->path2dataFolder . '\data.csv');
        $this->suppressionCombine();
    }

    public function suppressionCombine() {
        $suppressionFiles = scandir($this->path2suppressionFolder);
        array_shift($suppressionFiles);
        array_shift($suppressionFiles);

        foreach ($suppressionFiles as $suppressionFile) {
            $this->suppressData[] = CsvParseModel::specificCsv2array($this->path2suppressionFolder, $suppressionFile);
        }

        $break = 'point';
    }

} // END OF: RsmSuppress