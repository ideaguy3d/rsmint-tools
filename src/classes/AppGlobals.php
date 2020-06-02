<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: julius
 * Date: 2/27/2019
 * Time: 4:28 PM
 */

namespace Redstone\Tools;


class AppGlobals
{
    //-------------------------------------------------------------
    // TURN DEBUG MODE on OR off, then set the route in routes.php
    public static bool $NINJA_AUTO_DEBUG = false;
    //-------------------------------------------------------------
    
    //-------------------------------------------------------------
    // TURN TEST MODE on OR off, this is set for unit testing
    public static bool $NINJA_AUTO_TEST = false;
    //-------------------------------------------------------------
    
    // local path, NOT the production path
    private static string $LogFolderPath = 'logs';
    
    public static function PathToUploadDirectory() {
        //TODO: try to figure out how to cache whether current env is local or production
        return (gethostname() === 'Julius1')
            ? 'C:\xampp\htdocs\tools\uploads'
            : 'C:\inetpub\wwwroot\tools\uploads';
    }
    
    public static function isLocalHost(): bool {
        return (gethostname() === 'Julius1');
    }
    
    public static function rsLogInfo(string $info): string {
        // fopen(), fwrite(), fclose()
        $handle = null;
        $newLines = "\n\r\n\r";
        $info = substr_replace($info, $newLines, 0, 0);
        $info = substr_replace($info, $newLines, strlen($info), 0);
        
        // append all logs by day to the same file
        $date = getdate();
        $logDay = "\_RS_LOG_$date[month]-$date[mday]-$date[year]";
        // check if the dir exists, if not, create it
        if(!file_exists(self::$LogFolderPath)) {
            $cwdLog = getcwd() . self::$LogFolderPath;
            mkdir($cwdLog, 0777, true);
        }
        $filePath = self::$LogFolderPath . $logDay . '.txt';
        
        // using file_exists() may be better
        try {
            $ml = __METHOD__ . 'line: ' . __LINE__;
            $error = "File at $filePath could not be created ~AppGlobals.php ~$ml";
            // file already exists so just append to it.
            $handle = fopen($filePath, 'a') or exit($error);
            fwrite($handle, $info);
            return "logged to $filePath";
        }
        catch(\Throwable $e) {
            // create the file then write to it
            return $e->getMessage();
        }
        finally {
            fclose($handle);
        }
        
    } // END OF: LogComAutoInfo()
}