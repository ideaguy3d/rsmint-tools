<?php
/**
 * Created by PhpStorm.
 * User: Mike
 * Date: 8/6/2018
 * Time: 3:53 PM
 */

namespace Ninja\Client;

interface IAsciiDetect
{
    /**
     * With the full path to the CSV file this function will get the csv
     * file and remove all encoded upper ascii chars and 0-32 chars
     *
     * @param string $fullPathToCsv
     * @return array
     */
    public function stripCsvAscii(string $fullPathToCsv): array;
}