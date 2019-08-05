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
     * @param string $directory -
     * @param UploadedFile $uploadedFile -
     *
     * @return string - the path to the ???
     */
    public static function moveUploadedFile(
        App $app, string $directory, UploadedFile $uploadedFile
    ): string {
        
        $n = "\n\r\n\r";
        $log = $app->getContainer()->get('logger');
        
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
        
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        
        $info = "__>> RsmUploader.php L38 - Successfully uploaded file";
        $log->info("$n $info $n");
        
        return $filename;
    }
    
    /**
     * Same as the moveUploadedFile() but it will rename it to:
     * "accounting-php.csv" which is what ComAuto expects file name to be.
     *
     * @param App $app - the ref to the slim app
     * @param string $directory - the folder to ?!?
     * @param UploadedFile $uploadedFile - the uploaded file from within the slim app
     *
     * @return string -
     */
    public static function moveUploadedComAutoFile(
        App $app, string $directory, UploadedFile $uploadedFile
    ) {
    
    }
}
