<?php
declare(strict_types=1);

namespace Redstone\Tools;


class RsmSuppress
{
    public $status;
    
    public function __construct() {
        $this->status = 'RsmSuppress ready';
    }
    
    public function getStatus() {
        return $this->status;
    }
    
} // END OF: RsmSuppress