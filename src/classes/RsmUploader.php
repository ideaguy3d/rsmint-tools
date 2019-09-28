<?php
declare(strict_types=1);

namespace Redstone\Tools;

use Slim\App;
use Slim\Http\UploadedFile;

class RsmUploader
{
    /**
     * Move file to upload folder and give it a unique name
     *
     * @param App $app - this is reference to the Slim app
     * @param string $directory - the folder the file will be moved to
     * @param UploadedFile $uploadedFile - the uploaded CSV
     *
     * @return string - the new name of the uploaded file
     *
     */
    public static function moveUploadedFile(
        App $app, string $directory, UploadedFile $uploadedFile
    ): string {
        $n = "\n\r\n\r";
        
        // get the logger for debugging in production environment
        $log = $app->getContainer()->get('logger');
        
        return self::moveOp($uploadedFile, $log, $n, $directory, true);
    }
    
    /**
     * @param App $app
     * @param string $directory
     * @param array $uploadedFiles this param is an array of UploadedFile classes
     *
     * @return array - will return an array of the file names
     */
    public static function moveMultipleUploadedFiles(
        App $app, string $directory, array $uploadedFiles
    ): array {
        $fileNames = [];
        $n = "\n\r\n\r";
        
        // get the logger for debugging in production environment
        $log = $app->getContainer()->get('logger');
        
        foreach($uploadedFiles as $uploadedFile) {
            if($uploadedFile->getSize() === 0) continue;
    
            // looking for Slim\Http\UploadedFile
            $fileType = get_class($uploadedFile);
            $log->info(" | file type = $fileType | ");
            
            if($fileType === 'Slim\Http\UploadedFile') {
                $fileNames[] = self::moveOp(
                    $uploadedFile, $log, $n, $directory, false
                );
            }
        }
        
        return $fileNames;
    }
    
    /**
     * @param UploadedFile $uploadedFile
     * @param $log
     * @param string $n
     * @param string $directory
     * @param bool $doEncode
     *
     * @return string
     */
    private static function moveOp(
        UploadedFile $uploadedFile, $log, string $n, string $directory, bool $doEncode
    ): string {
        
        $filename = null;
    
        //TODO: EXIT program is any of the files are NOT csv
        if($doEncode) {
            $extension = pathinfo(
                $uploadedFile->getClientFilename(), PATHINFO_EXTENSION
            );
            try {
                // encode file name
                $basename = bin2hex(random_bytes(8));
            }
            catch(\Exception $e) {
                $message = $e->getMessage();
                $log->info("$n __>> EXCEPTION: $message $n");
                $basename = '';
            }
            $filename = sprintf('%s.%0.8s', $basename, $extension);
        }
        else {
            $filename = $uploadedFile->getClientFilename();
        }
        
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    
        $info = "__>> RsmUploader.php L102 - Successfully uploaded file";
        $log->info($info);
        
        return $filename;
    }
    
    /**
     * Same as the moveUploadedFile() but it will rename it to:
     * "accounting-php.csv" which is what ComAuto expects file name to be.
     *
     * @param App $app - the ref to the slim app
     * @param string $directory - the folder the file will be moved to
     * @param UploadedFile $uploadedFile - the uploaded file from within the slim app

     */
    public static function moveUploadedComAutoFile(
        App $app, string $directory, UploadedFile $uploadedFile
    ): void {
        $log = $app->getContainer()->get('logger');
        $extension = pathinfo(
            $uploadedFile->getClientFilename(), PATHINFO_EXTENSION
        );
        
        $log->info("\n__>> ComAuto File extension = $extension\n");
        
        if($extension !== 'csv') {
            exit("__>> ERROR: Only CSV files can be uploaded");
        }
        
        $filename = "accounting-php.csv";
        $moveTo = $directory . DIRECTORY_SEPARATOR . $filename;
        $log->info("\nMoving ComAuto file to: $moveTo\n");
        $uploadedFile->moveTo($moveTo);
    }
}
