<?php
/**
 *
 * Created by PhpStorm.
 * User: julius
 * Date: 10/12/2018
 * Time: 3:12 PM
 *
 */

define("RSM_DEBUG_MODE", true);
define("RSM_PRODUCTION_ENV", false);

$matches = [];
$uploadStatus = '';

if(!RSM_DEBUG_MODE) {
    $rawDmvTxtFileName = basename($_FILES['dmv-split']['name']);
    $production = 'C:\\inetpub\\wwwroot\\dmv\\ti\\';
    $localhost = './ti/';
    $pathToTxt = RSM_PRODUCTION_ENV ? $production : $localhost;
    $rawDmvTargetFile = $pathToTxt . $rawDmvTxtFileName;
    $rawDmvFileType = strtolower(pathinfo($rawDmvTargetFile, PATHINFO_EXTENSION));
    
    //TODO: do more validation checking on file e.g. is it expected file type? expected patterns? etc.
    
    if(move_uploaded_file($_FILES['dmv-split']['tmp_name'], $rawDmvTargetFile)) {
        $uploadStatus = "<p>File $rawDmvTargetFile has been uploaded</p>";
    }
    else {
        if(RSM_DEBUG_MODE) echo "<h2>ERROR - $rawDmvTxtFileName failed to upload</h2>";
        exit('Ending program since file did not upload');
    }
}
else {
    $production = 'C:\\inetpub\\wwwroot\\dmv\\ti\\';
    $localhost = './ti/';
    $pathToTxt = RSM_PRODUCTION_ENV ? $production : $localhost;
    $rawDmvTargetFile = glob('.\ti\*.txt')[0];
    echo "\n\nBreak Point\n\n";
}

//$rawDmvTxt = file_get_contents('.\SPM_DMV\MVP.txt');
$rawDmvTxt = file_get_contents(glob($pathToTxt . '*.txt')[0]);
$rows = preg_match_all("/.*\n/", $rawDmvTxt, $matches);
$matches = $matches[0];
$debugInfo = "";
$downloadLink = "";

$resultArr = dmvSplit($matches, $rawDmvTargetFile);
$debugInfo .= "<h5> __>> dmv file = $rawDmvTargetFile</h5>";

function dmvSplit(array $matches, string $dmvTxtPath): array {
    $rowsNormalized = [];
    
    // normalized rows counts, the 2nd dimension array index in $rowsNormalized
    $rowCount = 0;
    // the index in the 2nd dimension array of $rowsNormalized
    $fieldCount = 0;
    
    #region column counts and max's
    // 0) a column, VIN, size = 17
    $a_ColumnCount = 0;
    $a_ColumnMax = 17;
    
    // 1) b column, Class Code, size = 2
    $b_ColumnCount = 0;
    $b_ColumnMax = 1;
    
    // 2) c column, Plate, size = 8
    $c_ColumnCount = 0;
    $c_ColumnMax = 7;
    
    // 3) d column, Letter Mail By Date, size = 8
    $d_ColumnCount = 0;
    $d_ColumnMax = 7;
    
    // 4) e column, Inspection Expiration Date, size = 8
    $e_ColumnCount = 0;
    $e_ColumnMax = 7;
    
    // 5) f column, Mailer Due date, size = 8
    $f_ColumnCount = 0;
    $f_ColumnMax = 7;
    
    // 6) g column, Model Year, size = 4
    $g_ColumnCount = 0;
    $g_ColumnMax = 3; // ?
    
    // 7) h column, Make, size = 18
    $h_ColumnCount = 0;
    $h_ColumnMax = 17;
    
    // 8) i column, Model, size = 18
    $i_ColumnCount = 0;
    $i_ColumnMax = 17;
    
    // 9) j column, Date Of Birth, size = 8
    $j_ColumnCount = 0;
    $j_ColumnMax = 7;
    
    // 10) k column, Primary Owner, size = 38
    $k_ColumnCount = 0;
    $k_ColumnMax = 37;
    
    // 11) l column, Secondary Owner, size = 38
    $l_ColumnCount = 0;
    $l_ColumnMax = 37;
    
    // 12) m column, Street, size = 38
    $m_ColumnCount = 0;
    $m_ColumnMax = 37;
    
    // 13) n column, City, size = 22
    $n_ColumnCount = 0;
    $n_ColumnMax = 21;
    
    // 14) o column, State, size = 2
    $o_ColumnCount = 0;
    $o_ColumnMax = 1;
    
    // 15) p column, Zip Code, size = 9
    $p_ColumnCount = 0;
    $p_ColumnMax = 8;
    #endregion
    
    $activeColumn = 'A';
    
    // OUTPUT LOOP over rows
    foreach($matches as $row) {
        $fieldValue = '';
        // INPUT LOOP over "string glob row" to create normalized fields
        for($i = 0; $i < strlen($row); $i++) {
            $char = $row[$i];
            
            if(RSM_DEBUG_MODE) echo "\n__>> index $i char = $char\n";
            
            //-- get A column
            if($a_ColumnCount < $a_ColumnMax) {
                $a_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($a_ColumnCount === $a_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldCount++;
                $fieldValue = '';
                $fieldValue .= $char;
                $a_ColumnCount++;
                $activeColumn = 'B';
            }
            
            //-- get B column
            else if($b_ColumnCount < $b_ColumnMax) {
                $b_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($b_ColumnCount === $b_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $b_ColumnCount++;
                $activeColumn = 'C';
            }
            
            //-- get C column
            else if($c_ColumnCount < $c_ColumnMax) {
                $c_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($c_ColumnCount === $c_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $c_ColumnCount++;
                $activeColumn = 'D';
            }
            
            // get D column
            else if($d_ColumnCount < $d_ColumnMax) {
                $d_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($d_ColumnCount === $d_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $d_ColumnCount++;
                $activeColumn = 'E';
            }
            
            // get E column
            else if($e_ColumnCount < $e_ColumnMax) {
                $e_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($e_ColumnCount === $e_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $e_ColumnCount++;
                $activeColumn = 'F';
            }
            
            // get F column
            else if($f_ColumnCount < $f_ColumnMax) {
                $f_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($f_ColumnCount === $f_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $f_ColumnCount++;
                $activeColumn = 'G';
            }
            
            // get G column
            else if($g_ColumnCount < $g_ColumnMax) {
                $g_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($g_ColumnCount === $g_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $g_ColumnCount++;
                $activeColumn = 'H';
            }
            
            // get H column
            else if($h_ColumnCount < $h_ColumnMax) {
                $h_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($h_ColumnCount === $h_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $h_ColumnCount++;
                $activeColumn = 'I';
            }
            
            // get I column
            else if($i_ColumnCount < $i_ColumnMax) {
                $i_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($i_ColumnCount === $i_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $i_ColumnCount++;
                $activeColumn = 'J';
            }
            
            // get J column
            else if($j_ColumnCount < $j_ColumnMax) {
                $j_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($j_ColumnCount === $j_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $j_ColumnCount++;
                $activeColumn = 'K';
            }
            
            // get K column
            else if($k_ColumnCount < $k_ColumnMax) {
                $k_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($k_ColumnCount === $k_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $k_ColumnCount++;
                $activeColumn = 'L';
            }
            
            // get L column
            else if($l_ColumnCount < $l_ColumnMax) {
                $l_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($l_ColumnCount === $l_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $l_ColumnCount++;
                $activeColumn = 'M';
            }
            
            // get M column
            else if($m_ColumnCount < $m_ColumnMax) {
                $m_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($m_ColumnCount === $m_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $m_ColumnCount++;
                $activeColumn = 'M';
            }
            
            // get N column
            else if($n_ColumnCount < $n_ColumnMax) {
                $n_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($n_ColumnCount === $n_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $n_ColumnCount++;
                $activeColumn = 'M';
            }
            
            // get O column,
            else if($o_ColumnCount < $o_ColumnMax) {
                $o_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($o_ColumnCount === $o_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $o_ColumnCount++;
                $activeColumn = 'P';
            }
            
            // get P column, the last column :)
            else if($p_ColumnCount < $p_ColumnMax) {
                $p_ColumnCount++;
                $fieldValue .= $char;
            }
            else if($p_ColumnCount === $p_ColumnMax) {
                $rowsNormalized[$rowCount][$fieldCount] = $fieldValue;
                
                // clean field value
                $clean = trim($rowsNormalized[$rowCount][$fieldCount]);
                $clean = preg_replace("/\s{2,}/", " ", $clean);
                $rowsNormalized[$rowCount][$fieldCount] = $clean;
                
                // reset field
                $fieldValue = '';
                $fieldValue .= $char;
                $fieldCount++;
                $p_ColumnCount++;
                $activeColumn = '_DONE';
            }
            
            if(RSM_DEBUG_MODE) echo "\n\n__>> Processing Column $activeColumn row $rowCount\n\n";
            
            //-------------------------------------------------------
            // just so I can hit specific breakpoints while debugging
            //-------------------------------------------------------
            if($fieldCount === 4) {
                $break = 'point';
            }
            if($fieldCount === 8) {
                $break = 'point';
            }
            if($fieldCount === 15) {
                $break = 'point';
            } // end of debug breakpoints
            
        } // END OF inner-for-loop
        
        $rowCount++;
        $fieldCount = 0;
        if(RSM_DEBUG_MODE) echo "\n\n__>> Parsed all chars in cell.\n\n";
        
        // reset column counts after each iteration
        $a_ColumnCount = 0;
        $b_ColumnCount = 0;
        $c_ColumnCount = 0;
        $d_ColumnCount = 0;
        $e_ColumnCount = 0;
        $f_ColumnCount = 0;
        $g_ColumnCount = 0;
        $h_ColumnCount = 0;
        $i_ColumnCount = 0;
        $j_ColumnCount = 0;
        $k_ColumnCount = 0;
        $l_ColumnCount = 0;
        $m_ColumnCount = 0;
        $n_ColumnCount = 0;
        $o_ColumnCount = 0;
        $p_ColumnCount = 0;
    }
    
    $dmvOutputPath = str_replace('ti', 'to', $dmvTxtPath);
    $dmvOutputPath = str_replace('.txt', '.csv', $dmvOutputPath);
    
    $headerRow = [
        "VIN", "Class Code", "Plate", "Letter Mail By Date", "Inspection Expiration Date",
        "Mailer Due Date", "Model Year", "Make", "Model", "Date of Birth", "Primary Owner",
        "Secondary Owner", "Street", "City", "State", "Zip Code",
    ];
    
    // insert header row to the beginning of the array
    array_unshift($rowsNormalized, $headerRow);
    
    // add special field at end I'll call "rs_barcode" for now since we don't know what to name it yet
    $rowsNormalized[0][count($rowsNormalized)] = "rs_barcode"; // 1st add it header row
    
    // start at 1 to skip header row
    for($i = 1; $i < count($rowsNormalized); $i++) {
        $record = &$rowsNormalized[$i];
        $record[count($record)] = "*O{$record[0]}*";
        if(RSM_DEBUG_MODE) echo "\nbreakpoint\n";
    }
    
    //--------------------------
    //-- Start to write to CSV:
    //--------------------------
    $handle = fopen($dmvOutputPath, 'w')
    OR EXIT("ERROR - FAILED TO OPEN FILE STREAM ~ dmvSplit.php line 438 ish");
    
    foreach($rowsNormalized as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    unlink($dmvTxtPath); // done writing to CSV & deleting file
    
    return [
        $dmvOutputPath,
    ];
}

if(RSM_DEBUG_MODE) {
    echo "<p><i>breakpoint</i></p> <hr><hr><hr><hr>";
}

$debugInfo .= "<h5> __>> resultArr[0] = {$resultArr[0]}</h5>";

// getting the dir path to output csv
$localhost = "C:\\xampp\htdocs\\ninja\_dmv-split\\to\\";
$production = "C:\inetpub\wwwroot\dmv\\to\\";
$dirPath = RSM_PRODUCTION_ENV ? $production : $localhost;

//if(RSM_DEBUG_MODE)
$debugInfo .= "<h5> __>> dirPath = $dirPath</h5>";

if($dirPath === false) {
    exit ('ERROR - can not get output directory path');
}

if(RSM_DEBUG_MODE) {
    echo "<h1>$dirPath</h1>";
}

if($handle = opendir($dirPath)) {
    while(false !== ($entry = readdir($handle))) {
        if($entry != "." && $entry != "..") {
            $entry = str_replace(".php", " file", $entry);
            $queryString = str_replace("ti", "to", $rawDmvTargetFile);
            $queryString = str_replace('.txt', '.csv', $queryString);
            $queryString = basename($queryString);
            
            $debugInfo .= "<br><br><h5> __>> should download file = $queryString</h5>";
            $downloadLink .= "&nbsp;&nbsp;<a href='download.php?file=" . $queryString . "'> Download " . $entry . "</a><br><br>";
        }
    }
    closedir($handle);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>DMV Split</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

    <style>
        /* Sticky footer styles */
        html {
            position: relative;
            min-height: 100%;
        }

        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 60px; /* Set the fixed height of the footer here */
            line-height: 60px; /* Vertically center the text there */
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>

<!-- Horizontal Nav Bar at top of view -->
<div class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container d-flex justify-content-between">
        <a href="/" class="navbar-brand d-flex align-items-center">
            <!--<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>-->
            <strong>Redstone Print and Mail</strong>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#navbarHeader" aria-controls="navbarHeader"
                aria-expanded="false" aria-label="Toggle navigation">
            <a class="nav-link" href="./index.php" style="color: whitesmoke">Home</a>
        </button>
    </div>
</div>

<br><br>

<div class="container">
    <p>Download Link:</p>
    <!-- var downloadLink is already wrapped in <a>'s -->
    <?php echo $downloadLink ?>
    <hr>
    <p> Upload Status:</p>
    <p><?php echo $uploadStatus ?></p>
    <hr>
    <p>Debug info:</p>
    <!-- var debugInfo is already wrapped in <h5>'s -->
    <?php echo $debugInfo ?>
</div>

<footer class="footer">
    <div class="container">
        <p class="text-center">Redstone Print and Mail Intranet App v0.0.1</p>
    </div>
</footer>

</body>
</html>