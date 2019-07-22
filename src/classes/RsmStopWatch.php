<?php
declare(strict_types=1);

namespace Redstone\Tools;


class RsmStopWatch
{
    private static $startTimes = [];
    
    public static function start(string $timerName = 'default'): string {
        self::$startTimes[$timerName] = microtime(true);
        // when this function gets invoked, my perception will always be
        // start time = 0.0, it's always concatenated to a string
        return "0.0";
    }
    
    public static function elapsed(string $timerName = 'default'): float {
        return (microtime(true) - self::$startTimes[$timerName]);
    }
}