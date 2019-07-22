<?php

return function(string $rsCopy = 'copy'): void {
    $uploadInstructions = "Chose the .txt file to parse and do calculations on, a CSV will download when program completes";
    ?>
    <html lang="en">

    <head>
        <title>Redstone Mint</title>
        <style>
            body {
                font-family: sans-serif;
            }
        </style>
    </head>

    <body>

    <img src="https://redstonemail.com/companies/renderLogo/568ae6c2103fb4f64f2337d2"
         alt="logo" width="190px">

    <br>
    <hr>
    <br>

    <div style="margin: auto; width: 60%;">
        <h1>
            Applus Data Transform
        </h1>
        <p class="rsm-file-upload-info">
            <small>
                <?php echo $uploadInstructions ?>
            </small>
        </p>
        <br>
        <form action="./applus.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="job-recs" data-flex="60" style="margin-right: 6px;">
            <span onclick="console.log('the click was picked up :)')"><input type="submit" data-flex></span>
        </form>

        <br><br>

        <div id="rs-js-output"
             style="font-family: Consolas, sans-serif; font-size: 10px;"></div>

        <br><br><br><br>
        <?php echo $rsCopy ?>
    </div>

    <script type="text/javascript">
        let source = new EventSource('applus-events.php');
        let outputElem = document.getElementById('rs-js-output');

        source.onmessage = function (event) {
            console.log('__>> a new step has been completed');
            outputElem.appendChild(event.data + "<br>");
        };
    </script>

    </body>
    </html>

<?php } ?>

