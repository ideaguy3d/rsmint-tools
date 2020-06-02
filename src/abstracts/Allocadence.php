<?php
/**
 * Created by PhpStorm.
 * User: julius hernandez alvarado
 * Date: 6/1/2020
 * Time: 6:48 PM
 */

namespace Redstone\Tools;


abstract class Allocadence
{
    protected string $localDownloads = 'C:\Users\julius\Downloads';
    protected string $proDownloads = 'C:\Users\RSMADMIN\Downloads';
    protected bool $isLocal;
    
    public function __construct() {
        $this->isLocal = AppGlobals::isLocalHost();
    }
}