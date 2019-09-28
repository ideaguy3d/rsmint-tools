<?php

$file = $_GET['file'] ?? null;

if(file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    
    readfile('C:\xampp\htdocs\tools\uploads\suppressed_364b8687.zip');
    exit;
}
else {
    echo "ERROR - something broke :'(";
}




//