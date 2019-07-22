<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use \Ninja\Auto\RsmStopWatch;

RsmStopWatch::start();
$secTrack = 0.0;
while($_COOKIE['ApplusStatus'] !== "done" && $secTrack < 10.0) {
    if($secTrack % 0.2 === 0) {
        emitStatus($_COOKIE['ApplusStatus']);
    }
    
    $secTrack = RsmStopWatch::elapsed();
}

function emitStatus(string $message) {
    if(!headers_sent()) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
    }
    
    echo "data: $message";
    flush();
};
