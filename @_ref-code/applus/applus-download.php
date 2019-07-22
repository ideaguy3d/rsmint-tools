<?php

// _HARD CODED file name, by the time the program redirects to this file
// the data file should already have been created
$relativeFilePath = './to/rs_applus-data.csv';

if(file_exists($relativeFilePath)) {
    
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$relativeFilePath");
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: binary");
    
    readfile($relativeFilePath);
    unlink($relativeFilePath);
    
}
else {
    
    $exitInfo = "
        <h1 class='rsm-exit-message'>
            __>> ERROR - FILE NOT FOUND. No biggie, just Go to the
            <a href='./applus.php'>Landing</a> to start over.
        </h1>
    ";
    
    exit($exitInfo);
    
}

