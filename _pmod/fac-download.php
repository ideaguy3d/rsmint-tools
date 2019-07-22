<?php
$relativeFilePath = './co/rs_fac-dist.csv';

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
            <a href='./'>Landing</a> to start over.
        </h1>
        <p>It'll most likely work if you try again, if not contact julius@rsmail.com</p>
        <p><small>fac-download.php line 22, relative file path = '$relativeFilePath'</small></p>
    ";
    
    exit($exitInfo);
    
}


//