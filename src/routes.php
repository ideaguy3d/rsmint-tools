<?php
declare(strict_types=1);

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Redstone\Tools\RsmUploader;
use Redstone\Tools\EncodeRemove;
use Redstone\Tools\EncodeRemoveSql;
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
    
    /**  .17/redstone/tools
     *
     * a POST to '/' will invoke the core portion of the "encode remove" program.
     * It'll look for the CSV the user just uploaded, removed encodes, and download it
     *
     *  Q STRING:
     *    angularjs-id=VALUE
     */
    $app->post('/',
        function(Request $request, Response $response) use ($container, $app) {
            // function field initializations
            $directory = AppGlobals::PathToUploadDirectory();
            $log = $app->getContainer()->get('logger');
            $dbRSMint_1 = $this->dbRSMint_1;
            $uploadedFiles = $request->getUploadedFiles();
            $file = $uploadedFiles['csv_file'] ?? null;
            $AngularJS_id = $request->getQueryParam('angularjs-id');
            
            // function declarations
            $sanitizedFilePath = null;
            
            // in Debug Mode
            if(AppGlobals::$NINJA_AUTO_DEBUG) {
                // read a "debug test" file into memory
                $folder = 'C:\xampp\htdocs\redstone\uploads';
                $file = 'test.csv';
                $AngularJS_id = 'AngularJS_id';
                
                // debug EncodeRemove code & app logic
                $encodeRemove = new EncodeRemove($folder, $file, $dbRSMint_1, $AngularJS_id);
                $encodeRemove->removeEncodedChars();
                $sanitizedFilePath = $encodeRemove->getCleanFilePath();
                $log->info("\n\r__>> RSM DEBUG MODE - testing app logic, sanitized file path = $sanitizedFilePath\n\r");
                $encodeRemove->insertIntoSqlServer();
                $break = 'point';
            }
            
            // NOT in debug mode, it's go time
            else if($file && $file->getError() === UPLOAD_ERR_OK) {
                $fileName = RsmUploader::moveUploadedFile($app, $directory, $file);
                $encodeRemove = new EncodeRemove($directory, $fileName, $dbRSMint_1, $AngularJS_id);
                
                $log->info("\n\r __>> RSM file upload name= [ $fileName ] \n\r");
                
                $encodeRemove->removeEncodedChars();
                $sanitizedFilePath = $encodeRemove->getCleanFilePath();
                
                //-- headers to change to download a file --\\
                $cacheControl = 'Cache-Control';
                $contentDescription = 'Content-Description';
                $contentDisposition = 'Content-Disposition';
                $contentType = 'Content-Type';
                $contentTransferEncoding = 'Content-Transfer-Encoding';
                
                // test file path to see if this works
                $testFile = 'C:\xampp\htdocs\@ Good Prac Data' . DIRECTORY_SEPARATOR . "test.csv";
                $downloadFileName = 'rsm-encodes-removed.csv';
                
                $response = $response->withHeader($cacheControl, 'public');
                $response = $response->withHeader($contentDescription, 'File Transfer');
                $response = $response->withHeader($contentDisposition, "attachment; filename=$downloadFileName");
                $response = $response->withHeader($contentType, 'application/zip');
                $response = $response->withHeader($contentTransferEncoding, 'binary');
                
                // have to add '.csv' to fix an annoying error
                readfile(($sanitizedFilePath . '.csv'));
                
                $encodeRemove->insertIntoSqlServer();
                
                return $response;
            }
            // something broke
            else {
                $errorInfo = '<h1>No file selected and the submit button was clicked</h1> <br/>';
                $fileError = 'Error ' . (string)$file->getError();
                $startOver = "<br><br> <a href='/redstone/tools/encode-remove'><b>Start Over</b></a>";
                return $response->getBody()->write($errorInfo . $fileError . $startOver);
            }
            
            exit("An error happened ~routes.php L93");
        }
    );
    
    /**  .17/redstone/tools
     *
     * a GET req, this route will render the AngularJS UI so the user can upload a file
     */
    $app->get('/encode-remove',
        function(Request $request, Response $response, array $args) use ($container) {
            $ng = "angularjsphpsqlcompsciwebapp";
            $ngid = "ng" . rand(0, 9000000) . $ng[(rand(0, strlen($ng)) - 1)];
            $args['ngid'] = $ngid;
            $args['php_action'] = "?angularjs-id=$ngid";
            $container->get('renderer')->render($response, 'encode-remove.phtml', $args);
        }
    );
    
    /**  .17/redstone/tools/get-removed-encodes/{ng-id}
     *
     *  Return the info for the removed encodes from SQL Server
     */
    $app->get('/get-removed-encodes/{ng-id}',
        function(Request $request, Response $response, array $args) use ($container) {
            $RSMint_1 = $this->dbRSMint_1;
            $db = new EncodeRemoveSql($RSMint_1, $args['ng-id']);
            return $response->withJson($db->getRemovedEncodes());
        }
    );
    
    /**  .17/redstone/tools
     *
     * Root route to The API for the slim micro framework, this won't be used much to be honest
     * All it'll do is render a view that'll say "hello" what ever var {name} is
     */
    $app->get('/[{name}]',
        function(Request $request, Response $response, array $args) use ($container) {
            
            // Sample log message
            $container->get('logger')->info("\n\rRedstone '/' route\n\r");
            
            // Render index view
            return $container->get('renderer')->render($response, 'index.phtml', $args);
            
        } // END OF: root route ctrl i.e. "/[{optional-route-var}]"
    
    );
    
};
