<?php
declare(strict_types=1);

use Redstone\Tools\CsvParseModel;
use Redstone\Tools\RsmSuppress;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Redstone\Tools\RsmUploader;
use Redstone\Tools\EncodeRemove;
use Redstone\Tools\EncodeRemoveSql;
use Redstone\Tools\AppGlobals;


return function(App $app) {
    
    if(!empty(AppGlobals::$NINJA_AUTO_DEBUG) && AppGlobals::$NINJA_AUTO_DEBUG) {
        /*
            .17/.../street-view/user/mhetauser!@/lindsey@rsmail.com
            .17/.../comauto/start/a/{action}

            //-- To do a "job board data mash" sql server insert:
            .17/.../comauto/start/a/run?precision=exact&comauto-sql-insert=2
        */
        
        $_SERVER['REQUEST_URI'] = 'tools/suppress';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
    
    $container = $app->getContainer();
    
    /**  .17/redstone/tools/
     *
     * a POST to '/' will invoke the core portion of the "encode remove" program.
     * It'll look for the CSV the user just uploaded, removed encodes, and download it
     *
     *  ?angularjs-id=VALUE
     */
    $app->post('/',
        function(Request $request, Response $response) use ($container, $app) {
            // function field initializations
            $directory = AppGlobals::PathToUploadDirectory();
            $log = $app->getContainer()->get('logger');
            // get the db
            $dbRSMint_1 = $this->dbRSMint_1;
            // get uploaded files
            $uploadedFiles = $request->getUploadedFiles();
            $file = $uploadedFiles['csv_file'] ?? null;
            // get query param
            $AngularJS_id = $request->getQueryParam('angularjs-id');
            
            // function declarations
            $sanitizedFilePath = null;
            
            // in Debug Mode
            if(AppGlobals::$NINJA_AUTO_DEBUG) {
                // read a "debug" file into memory
                $folder = 'C:\xampp\htdocs\redstone\uploads';
                $file = 'debug.csv';
                $AngularJS_id = 'AngularJS_id';
                
                // debug EncodeRemove code & app logic
                $encodeRemove = new EncodeRemove($folder, $file, $dbRSMint_1, $AngularJS_id);
                $encodeRemove->removeEncodedChars();
                $sanitizedFilePath = $encodeRemove->getCleanFilePath();
                $log->info("\n\r__>> RSM DEBUG MODE - testing app logic, sanitized file path = $sanitizedFilePath\n\r");
                $encodeRemove->insertIntoSqlServer();
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
                // _HARD CODED FILE NAME
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
            
            // something broke :'(
            else {
                $errorInfo = '<h1>No file selected and the submit button was clicked</h1> <br/>';
                $fileError = 'Error ' . (string)$file->getError();
                $startOver = "<br><br> <a href='/redstone/tools/encode-remove'><b>Start Over</b></a>";
                return $response->getBody()->write($errorInfo . $fileError . $startOver);
            }
            
            exit("An error happened ~routes.php L93");
        }
    );
    
    /**
     * This will be the route that does the Allocadence PO and Receiving to QuickBooks mapping
     *
     * ?po=<yes||no>&rec=<yes||no>
     */
    $app->get('/alloc/qb',
        function(Request $request, Response $response) use ($container, $app) {
            $qStr = $request->getQueryParams();
            // the Purchase Order query value
            $po = $qStr['po'] ?? null;
            // the Receiving query value
            $rec = $qStr['rec'] ?? null;
            
        }
    );
    
    /** .17/redstone/tools/comauto-upload2/upload
     *
     * The ComAuto self service iframe will post a file to this route
     * so that the normal process of of "comauto" can get invoked.
     */
    $app->post('/comauto-upload2/upload',
        function(Request $request, Response $response) use ($container, $app) {
            $log = $container->get('logger');
            $comautoFolder = AppGlobals::PathToNinjacommissionCsvDirectory();
            $uploadedFiles = $request->getUploadedFiles();
            $file = $uploadedFiles['csv_file'] ?? null;
            
            //TODO: may want to scan uploaded file to make sure it has the correct fields for comauto
            
            // we're in debug mode
            if(AppGlobals::$NINJA_AUTO_DEBUG) {
                $debugFolder = '';
            }
            // NOT in debug mode
            else if($file && $file->getError() === UPLOAD_ERR_OK) {
                RsmUploader::moveUploadedComAutoFile($app, $comautoFolder, $file);
            }
            // file upload broke
            else {
                $errorInfo = '<h1>No file selected and the submit button was clicked</h1> <br/>';
                $fileError = 'Error ' . (string)$file->getError();
                $startOver = "<br><br> <a href='/redstone/tools/encode-remove'><b>Start Over</b></a>";
                return $response->getBody()->write($errorInfo . $fileError . $startOver);
            }
            
            //exit("An error happened ~routes.php L140");
            $newResponse = $response->withStatus(200);
            return $newResponse;
        }
    );
    
    /**  .17/redstone/tools
     *
     * a GET req, this route will render the AngularJS UI so the user can upload a file
     */
    $app->get('/encodes',
        function(Request $request, Response $response, array $args) use ($container) {
            $ng = "aquickbrownfoxjumpsoverthelazydog";
            $ngid = "ng" . rand(0, 9000000) . $ng[(rand(0, strlen($ng)) - 1)];
            $args['ngid'] = $ngid;
            $args['php_action'] = "?angularjs-id=$ngid";
            
            return $container->get('renderer')->render($response, 'temp.encode-remove.phtml', $args);
        }
    );
    
    /**
     * This will be the UI for the suppression list tool that I will use heavily to run
     * jobs and that other members of the Redstone team can also use if they want.
     */
    $app->get('/suppress',
        function(Request $request, Response $response, array $args) use ($container) {
            // just return the view
            return $container->get('renderer')->render($response, 'temp.suppress.phtml', $args);
        }
    );
    
    /**
     * When the user presses the submit button to upload the suppression list files this route
     * will get those files then send the user to the path to download the results
     */
    $app->post('/suppress/upload',
        function(Request $request, Response $response, array $args) use ($container, $app) {
            $directory = AppGlobals::PathToUploadDirectory();
            $log = $app->getContainer()->get('logger');
            
            // get the db
            //$dbRSMint_1 = $this->dbRSMint_1;
            
            // get uploaded files
            $uploadedFiles = $request->getUploadedFiles();
            $baseFile = $uploadedFiles['base_file'] ?? null;
            $suppressFiles = $uploadedFiles['suppress'] ?? null;
            $suppressFiles = array_filter($suppressFiles);
            
            // function declarations
            $sanitizedFilePath = null;
            
            // in Debug Mode
            if(AppGlobals::$NINJA_AUTO_DEBUG) {
                // read a "debug" file into memory
                $folder = 'C:\xampp\htdocs\redstone\uploads';
                $file = 'debug.csv';
                
                $info = "__>> RSM DEBUG MODE - testing app logic, suppression file path = ";
                $log->info("\n\r$info $sanitizedFilePath\n\r");
                return $response->getBody()->write('In Debug Mode.');
            }
            
            //-- NOT in debug mode, it's go time --\\
            else if(
                $baseFile && $baseFile->getError() === UPLOAD_ERR_OK && $suppressFiles
            ) {
                $baseFileName = RsmUploader::moveUploadedFile($app, $directory, $baseFile);
                $suppressionFileNames = RsmUploader::moveMultipleUploadedFiles(
                    $app, $directory, $suppressFiles
                );
                
                $suppressFilesPrint = print_r($suppressFiles, true);
                $log->info("[ suppression files = $suppressFiles ]");
                
                $suppress = new RsmSuppress($baseFileName, $suppressionFileNames, $log);
                $suppress->suppressionStart();
                
                //-- headers to change to download a file --\\
                $cacheControl = 'Cache-Control';
                $contentDescription = 'Content-Description';
                $contentDisposition = 'Content-Disposition';
                $contentType = 'Content-Type';
                $contentTransferEncoding = 'Content-Transfer-Encoding';
                
                
                /* -- test file path to see if this works --
                $testSuppressed = 'C:\xampp\htdocs\tools\uploads\test\suppressed_test.csv';
                $testSuppressed = str_replace('\\', '/', $testSuppressed);
                $testRemoved = 'C:\xampp\htdocs\tools\uploads\test\removed_test.csv';
                $testRemoved = str_replace('\\', '/', $testRemoved);
                */
                
                
                $suppressed = $suppress->fullPathToSuppressed;
                $removed = $suppress->fullPathToRemoves;
                $files = [$suppressed, $removed];
                $sid = strstr(basename($suppressed), '.csv', true);
                $log->info("_| suppression id = $sid |_");
                $log->info("_| full path to suppressed = $suppressed |_");
                $log->info("_| full path to removed = $removed |_");
                
                // START the .zip part
                $zipName = $suppress->exportPath . DIRECTORY_SEPARATOR . "$sid.zip";
                $log->info("_| zip name = $zipName |_");
                $zip = new ZipArchive();
                $zip->open($zipName, ZipArchive::CREATE);
                foreach($files as $file) {
                    $fileName = basename($file);
                    $zip->addFile($file, $fileName);
                }
                $zip->close();
                
                // START the file download part
                /* -- old way --
                $response = $response->withHeader($cacheControl, 'public');
                $response = $response->withHeader($contentDescription, 'File Transfer');
                $response = $response->withHeader($contentDisposition, "attachment; filename=suppression.zip");
                $response = $response->withHeader($contentType, 'application/zip');
                $response = $response->withHeader($contentTransferEncoding, 'binary');
                
                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Transfer-Encoding: binary');
                header('Content-Disposition: attachment; filename="'.basename($zipName).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($zipName));
    
                $log->info("_| 2nd check, zip name = $zipName |_");
                readfile($zipName);
                */
                
                //sleep(2);
                
                return $response->withRedirect("../../ap2.php?file=$zipName");
            }
            
            // something broke :'(
            else {
                $errorInfo = '<h1>No file selected and the submit button was clicked</h1> <br/>';
                $fileError = 'Error ';
                $startOver = "<br><br> <a href='/redstone/tools/encode-remove'><b>Start Over</b></a>";
                return $response->getBody()->write($errorInfo . $fileError . $startOver);
            }
        }
    );
    
    /**  .17/redstone/tools/comauto
     *
     * this will render the file upload tool to allow comauto to become self service
     * it is intended to be iframe'd from the UI being built with the grunt build system
     *
     * ERROR info:
     * I have to suffix a "2" because for some reason when the template file named is changed
     * for the route a "page not found" error happens so giving a new route name when I change
     * template file name fixes this.
     */
    $app->get('/comauto-upload2',
        function(Request $request, Response $response, array $args) use ($container) {
            $container->get('renderer')->render($response, 'temp.comauto-upload.phtml', $args);
        }
    );
    
    /**  .17/redstone/tools/get-removed-encodes/{ng-id}
     *
     * Return the info for the removed encodes from SQL Server
     */
    $app->get('/get-removed-encodes/{ng-id}',
        function(Request $request, Response $response, array $args) use ($container) {
            $RSMint_1 = $this->dbRSMint_1;
            $db = new EncodeRemoveSql($RSMint_1, $args['ng-id']);
            
            return $response->withJson($db->getRemovedEncodes());
        }
    );
    
    /**
     * The future route to render the facility distribution app once it get's migrated away from ninja
     */
    $app->get('/fac-dist',
        function(Request $request, Response $response, array $args) use ($container) {
            //TODO: render the facility distribution app from .phtml
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
            return $container->get('renderer')->render($response, 'temp.index.phtml', $args);
            
        } // END OF: root route ctrl i.e. "/[{optional-route-var}]"
    );
    
};
