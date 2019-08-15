<?php

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

use Redstone\Tools\ComingleCombine;

$comingle = new ComingleCombine();

$comingle->startExtract();

$totalRecs = $comingle->getTotalRecs();

$break = 'point';