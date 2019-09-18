<?php
declare(strict_types=1);

namespace Redstone\Tools;

abstract class RsmSuppressAbstract
{
    private $status;
    
    public function __construct() {
        $this->status = 'RsmSuppressAbstract Ready';
    }
    
    protected function getStatus() {
        return $this->status;
    }
    
    abstract protected function suppressionCombine();
}