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

<!-- horiz nav bar at the top of the view -->
<div class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container d-flex justify-content-between">
        <a href="/" class="navbar-brand d-flex align-items-center">
            <!-- a raw SVG icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                <circle cx="12" cy="13" r="4"></circle>
            </svg>

            <strong>Redstone Print and Mail</strong>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#navbarHeader" aria-controls="navbarHeader"
                aria-expanded="false" aria-label="Toggle navigation">
            <a class="nav-link" href="/" style="color: whitesmoke">
                Home
            </a>
        </button>
    </div>
</div>

<br><br>

<div class="container">
    <h3>DMV Split Text</h3>

    <br>
    
    <div class="col-sm-8 col-md-4 col-lg-3">
        <form action="dmvSplit.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="dmv-split">Upload "DMV Set Width" .txt file,</label>
                <!-- Choose File btn -->
                <input type="file" name="dmv-split" id="dmv-split" class="form-control-file"
                       aria-describedby="split dmv file" placeholder="DMV.txt">
            </div>

            <!-- The submit button -->
            <button type="submit" class="btn btn-primary btn-lg">Parse DMV .txt file</button>

            <!-- Info below submit button -->
            <p><b>This can take up to 2+mins</b>, <i>so please be patient.</i></p>
            <p class="rsm-processing-info">
                <small>
                    There will be a "processing" indicator in the web browsers' tab that'll
                    show the page is not frozen
                </small>
            </p>
        </form>
    </div>
</div><!-- END OF div.container -->

<footer class="footer">
    <div class="container">
        <p class="text-center">Redstone Print and Mail Intranet App v0.0.1</p>
    </div>
</footer>

</body>
</html>