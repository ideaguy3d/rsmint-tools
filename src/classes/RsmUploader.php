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
     * @param App $app
     * @param string $directory
     * @param UploadedFile $uploadedFile
     *
     * @return string
     */
    public static function moveUploadedFile(App $app, string $directory, UploadedFile $uploadedFile): string {
        
        $n = "\n\r\n\r";
        $log = $app->getContainer()->get('logger');
        
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        try {
            $basename = bin2hex(random_bytes(8));
        }
        catch(\Exception $e) {
            $message = $e->getMessage();
            $log->info("$n __>> EXCEPTION: $message $n");
            $basename = '';
        }
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        
        $log->info("$n __>> RsmUploader.php L38 - Successfully uploaded file $n");
        
        return $filename;
    }
}
