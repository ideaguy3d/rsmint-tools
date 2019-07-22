<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 11/1/2018
 * Time: 7:17 PM
 */

// To autoload custom class objects:
require '..\vendor\autoload.php';

use Ninja\Auto\SuppressionList;

// base data set
$csvBasePath = '.\base';

// the suppression list
$csvDupePath = '.\suppression';

$arrCsvBase = \Ninja\Auto\CsvParseModel::csv2array($csvBasePath);
$arrCsvSuppress = \Ninja\Auto\CsvParseModel::csv2array($csvDupePath);

$suppress = new SuppressionList($arrCsvBase, $arrCsvSuppress);
$dataSetSuppressed = $suppress->job61120suppress();

echo "\n\n__>> Starting CSV export \n\n";

\Ninja\Auto\CsvParseModel::export2csv($dataSetSuppressed, './', 'php-suppressed');

echo "\nbreakpoint\n";








// end of PHP file