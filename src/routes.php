<?php
declare(strict_types=1);

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Redstone\Tools\RsmUploader;
use Redstone\Tools\RsmEncodeRemove;

return function(App $app) {
    
    $container = $app->getContainer();
    
    $app->post('/',
        function(Request $request, Response $response) use ($container, $app) {
            $directory = $container->get('upload_directory');
            
            $uploadedFiles = $request->getUploadedFiles();
            
            $file = $uploadedFiles['csv_file'];
            if($file->getError() === UPLOAD_ERR_OK) {
                $homeLink = "<br/> <a href=''>Home</a>";
                $fileName = RsmUploader::moveUploadedFile($app, $directory, $file);
                
                // some html info
                $fileInfo = '<br/> &nbsp;&nbsp; ' .'uploaded: ' . $fileName;
                $folderInfo = "to $directory<br/>$homeLink";
                
                //-- headers to change:
                $cacheControl = 'Cache-Control';
                $contentDescription = 'Content-Description';
                $contentDisposition = 'Content-Disposition';
                $contentType = 'Content-Type';
                $contentTranserEncoding = 'Content-Transfer-Encoding';
                // test file path to see if this works
                $testFile = 'C:\xampp\htdocs\@ Good Prac Data' . DIRECTORY_SEPARATOR . "test.csv";
                
                $response = $response->withHeader($cacheControl, 'public');
                $response = $response->withHeader($contentDescription, 'File Transfer');
                $response = $response->withHeader($contentDisposition, "attachment; filename=RedstoneFile.csv");
                $response = $response->withHeader($contentType, 'application/zip');
                $response = $response->withHeader($contentTranserEncoding, 'binary');
                
                readfile($testFile); 
                
                // send back info to user
                return $response;
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
