<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 10/24/2018
 * Time: 4:09 PM
 */

namespace Rsm\ElevateRecoveries;

interface ElevateRecoveriesInterface
{
    /**
     * This function is essentially going to "right shift up group" the raw data
     * based on a key this function will define
     */
    public function elevate();
    
    /**
     * I think this function is how I know how many fields to
     * dynamically create.
     *
     * -- As of 10-30-2018:
     * This function depends on A LOT of hard coded variables. Currently the raw
     * data set has to have the same exact fields every time.
     * It has to always be in the same column order every time as well.
     *
     * @param int $arrSize - the size of the current records being iterated
     */
    public function trackAppends(int $arrSize): void;
}