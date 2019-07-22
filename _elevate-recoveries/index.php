<?php
// keeping code here to refresh my memory in future of how to get the systems temp folder.
//echo sys_get_temp_dir();
$t = gettype(sys_get_temp_dir());
//echo "<br>var t = $t<br>";
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

        body {
            margin-bottom: 60px; /* Margin bottom by footer height */
        }

        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 60px; /* Set the fixed height of the footer here */
            line-height: 60px; /* Vertically center the text there */
            background-color: #f5f5f5;
        }

        /* Custom page CSS
        --------------------------------------------------
        // Not required for template or sticky footer method.
         .container {
            width: auto;
            max-width: 680px;
            padding: 0 15px;
        }
        */
    </style>
</head>


<body>

<br>

<div class="container">
    <h3>Elevate Recoveries algorithm 1</h3>

    <br>

    <div class="row">
        <div class="col-sm-8 col-md-4 col-lg-3">
            <form action="elevateRecoveries.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="dmv-split">Upload Data</label>
                    
                    <input type="file" name="elr-right-shift-up-group" id="elr-right-shift-up-group"
                           class="form-control-file" aria-describedby="split dmv file" placeholder="DMV.txt">
                </div>
                
                <!-- The submit button -->
                <button type="submit" class="btn btn-primary btn-lg">Parse Data File</button>
            </form>
        </div>
        
        <!-- data info vid -->
        <div class="col-sm-8 col-md-8 col-lg-9">
            <p>What the algorithm will do to the data:</p>
            <video src="./vids/php-sql.mp4" width="534" height="330" controls>
                <source src="./vids/php-sql.mp4" type="video/mp4">
            </video>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p class="text-center">Redstone Print and Mail Intranet App v0.0.1</p>
    </div>
</footer>

</body>
</html>


