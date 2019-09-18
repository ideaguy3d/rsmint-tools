<?php
declare(strict_types=1);

namespace Redstone\Tools;


class RsmSuppress extends RsmSuppressAbstract
{
    public $status;
    private $path2dataFolder;
    private $path2suppressionFolder;

    private $suppressData;

    /**
     * The base data. This data will have to get records removed based on
     * the suppression lists.
     *
     * @var array
     */
    private $baseData;

    public function __construct() {
        parent::__construct();
        $this->status = 'RsmSuppress ready';

        //TODO: dynamically check the host to determine local or pro env
        $this->path2dataFolder = 'E:\redstone\uploads\77542\data';
        $this->path2suppressionFolder = 'E:\redstone\uploads\77542\suppress';
        $this->readFiles(); // this may be verbose
        $this->suppressionCombine();
    }

    public function getStatus() {
        return parent::getStatus() . ' > ' . $this->status;
    }

    public function readFiles() {
        $this->baseData = CsvParseModel::csv2array($this->path2dataFolder);
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