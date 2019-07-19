<?php
declare(strict_types=1);

namespace Redstone\Tools;


class RsmEncodeRemove
{
    /**
     * @var string
     */
    private $path2file;
    /**
     * @var array
     */
    private $csvData;
    
    public function __construct(string $directory, string $fileName) {
        $this->path2file = $directory . DIRECTORY_SEPARATOR . $fileName;
        $this->csvData = CsvParseModel::specificCsv2array($directory, $fileName);
    }
    
    /**
     * will parse CSV data, then export the clean data to a CSV file
     */
    public function removeEncodedChars(): void  {
        $break = 'point';
        
        // do stuff
        
    }
    
    public function getCleanFilePath(): string {
    
    }
}