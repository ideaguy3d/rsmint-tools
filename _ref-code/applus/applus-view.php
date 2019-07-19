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

    <div style="margin: auto; width: 60%;">
        <h1>
            <img src="https://redstonemail.com/companies/renderLogo/568ae6c2103fb4f64f2337d2"
                 alt="logo" width="150px">

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

        <br>

        <div id="rs-js-output" style="font-family: Consolas, sans-serif; font-size: 12px;"></div>
        
        <?php echo $rsCopy ?>
    </div>

    <script type="text/javascript">
        let outputElem = document.getElementById('rs-js-output');
        let applusCookie = getCookie('ApplusStatus');

        setInterval(function () {
            let applusCookie = getCookie('ApplusStatus');
            outputElem.innerHTML = "<h2>" + applusCookie + "</h2>";
        }, 2);

        function getCookie(cname) {
            let name = cname + "=";
            let ca = decodeURIComponent(document.cookie).split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) === 0) {
                    return c.substring(name.length, c.length);
                }
            }

            return "";
        }
    </script>

    </body>
    </html>

<?php } ?>

