<?php
declare(strict_types=1);

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Redstone\Tools\RsmUploader;
use Redstone\Tools\RsmEncodeRemove;
use Redstone\Tools\AppGlobals;

if(!empty(AppGlobals::$NINJA_AUTO_DEBUG) && AppGlobals::$NINJA_AUTO_DEBUG) {
    /*
        .17/.../street-view/user/mhetauser!@/lindsey@rsmail.com
        .17/.../comauto/start/a/{action}
    
        //-- To do a "job board data mash" sql server insert:
        .17/.../comauto/start/a/run?precision=exact&comauto-sql-insert=2
    */
    
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
}

return function(App $app) {
    
    $container = $app->getContainer();
    
    // route root
    $app->post('/',
        function(Request $request, Response $response) use ($container, $app) {
            $directory = $container->get('upload_directory');
            $log = $app->getContainer()->get('logger');
            
            $uploadedFiles = $request->getUploadedFiles();
            $file = $uploadedFiles['csv_file'] ?? null;
            
            // Program is in debug mode
            if(AppGlobals::$NINJA_AUTO_DEBUG) {
                // read a "debug test" file into memory
                $folder = 'C:\xampp\htdocs\redstone\uploads';
                $file = 'test.csv';
                
                // debug RsmEncodeRemove code & app logic
                $encodeRemove = new RsmEncodeRemove($folder, $file);
                $encodeRemove->removeEncodedChars();
                $cleanFilePath = $encodeRemove->getCleanFilePath();
            }
            // Program is NOT in debug mode, it's go time
            else if($file && $file->getError() === UPLOAD_ERR_OK) {
                $fileName = RsmUploader::moveUploadedFile($app, $directory, $file);
                $encodeRemove = new RsmEncodeRemove($directory, $fileName);
                $log->info("\n\r __>> file name = $fileName \n\r");
                $encodeRemove->removeEncodedChars();
                $cleanFile = $encodeRemove->getCleanFilePath();
                
                //-- headers to change to download a file --\\
                $cacheControl = 'Cache-Control';
                $contentDescription = 'Content-Description';
                $contentDisposition = 'Content-Disposition';
                $contentType = 'Content-Type';
                $contentTransferEncoding = 'Content-Transfer-Encoding';
                
                // test file path to see if this works
                $testFile = 'C:\xampp\htdocs\@ Good Prac Data' . DIRECTORY_SEPARATOR . "test.csv";
                
                $response = $response->withHeader($cacheControl, 'public');
                $response = $response->withHeader($contentDescription, 'File Transfer');
                $response = $response->withHeader($contentDisposition, "attachment; filename=encodes_removed.csv");
                $response = $response->withHeader($contentType, 'application/zip');
                $response = $response->withHeader($contentTransferEncoding, 'binary');
                
                readfile($cleanFile);
                
                return $response;
            }
            // something broke
            else {
                exit("ERROR UPLOADING FILE - " . (string)$file->getError());
            }
        }
    );
    
    $app->get('/encode-remove',
        function(Request $request, Response $response, array $args) use ($container) {
            $container->get('renderer')->render($response, 'encode-remove.phtml', $args);
        }
    );
    
    /**
     * Root route to The API for the slim micro framework, this won't be used much to be honest
     */
    $app->get('/[{name}]',
        
        // route controller
        function(Request $request, Response $response, array $args) use ($container) {
            
            // Sample log message
            $container->get('logger')->info("\n\rRedstone '/' route\n\r");
            
            // Render index view
            return $container->get('renderer')->render($response, 'index.phtml', $args);
            
        } // END OF: root route ctrl i.e. "/[{optional-route-var}]"
    
    );
    
};
