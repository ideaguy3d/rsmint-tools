<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 10/24/2018
 * Time: 4:25 PM
 */

namespace Rsm\ElevateRecoveries;

interface ElrCsvParseModelInterface
{
    /**
     * Get the CSV file, transform it to an array, then return it.
     * should be a full path e.g. "C:\foo\bar\csv_folder"
     * this would be used if there was a local array
     *
     * @param string $path
     * @return array
     */
    public static function csv2array(string $path): array;
    
    /**
     * Convert an array to CSV and put it in some location
     *
     * @param array $dataSet        -
     * @param string $exportPath    -
     * @param string $dataType      -
     *
     * @return string
     */
    public static function export2csv(array $dataSet, string $exportPath, string $dataType): string;
}