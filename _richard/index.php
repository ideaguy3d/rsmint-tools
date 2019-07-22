<?php
/**
 * Created by PhpStorm.
 * User: julius
 * Date: 8/28/2018
 * Time: 4:14 PM
 */

$toolInfoH1 = "<h1 class='text-center'> &nbsp;Richard Charlow tools <small><a href=\"/\"><small>Redstone Mint Home</small></a></small></h1><br>";

?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Richard Charlow</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

    <style>
        /* Sticky footer styles  */
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

        .rsm-inline-block {
            display: inline-block;
            height: 100%;
            margin: 12px;
        }
        
        iframe.rsm-applus {
            width: 100%;
            height: 400px;
        }
    </style>
</head>

<body>

<div class="container">
    <br>
    <?= $toolInfoH1 ?>

    <iframe src="applus.php" frameborder="0" class="rsm-applus"></iframe>

    <h3>Other Data Tools</h3>
    <hr>
    
    <div class="row">
        <div class="rsm-inline-block">
            <h3>Loan Officer Delegate</h3>
            <form action="loanOfficer.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="loan_officers_info">Upload Loan Officers info</label>
                    <input type="file" name="loan_officers[]" id="loan_officers_info" class="form-control-file"
                           aria-describedby="emailHelp" placeholder="Loan Officers">
                </div>

                <div class="form-group">
                    <label for="loan_officers_data">Upload Data</label>
                    <input type="file" name="loan_officers[]" id="loan_officers_data" class="form-control-file"
                           aria-describedby="emailHelp" placeholder="Loan Officers">
                </div>

                <button type="submit" class="btn btn-primary btn-lg">Loan Officer Delegate</button>
            </form>
        </div>

        <div class="rsm-inline-block">
            <h3>Right Shift Up Group</h3>
            <form action="rightShiftUpGroup.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="right_shift_up">Upload Data</label>
                    <input type="file" name="loan_officers[]" id="right_shift_up" class="form-control-file"
                           aria-describedby="emailHelp" placeholder="Loan Officers">
                </div>

                <button type="submit" class="btn btn-primary btn-lg">Right Shift Up Group</button>
            </form>
        </div>

        <div class="rsm-inline-block">
            <h3>Suppression List</h3>
            <form action="suppressionList.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="suppression_list">Upload Suppression List</label>
                    <input type="file" name="suppression_list_files[]" id="suppression_list" class="form-control-file"
                           aria-describedby="emailHelp" placeholder="Loan Officers">
                </div>

                <div class="form-group">
                    <label for="suppression_data">Upload Data</label>
                    <input type="file" name="loan_officers[]" id="suppression_data" class="form-control-file"
                           aria-describedby="emailHelp" placeholder="Loan Officers">
                </div>

                <button type="submit" class="btn btn-primary btn-lg">Suppression List</button>
            </form>
        </div>
    </div>


</div>
<!-- END OF div.container -->

<br>

<footer class="footer">
    <div class="container">
        <h1 class="text-center">version 0.0.1</h1>
    </div>
</footer>

</body>

</html>