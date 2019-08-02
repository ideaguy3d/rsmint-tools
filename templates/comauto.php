<?php

$appName = "ComAuto self service.";

?>

<!DOCTYPE html>
<html lang="en" data-ng-app="rsm-comauto-self-service">

<head>
    <title>Redstone Mint</title>
    <style>
        body {
            font-family: sans-serif;
        }
        
        table {
            margin: auto;
        }
        
        .rsm-text-center {
            text-align: center;
        }
        
        .rsm-mt {
            margin-top: 10%;
        }
        
        .flat-table {
            margin-bottom: 20px;
            border-collapse: collapse;
            font-family: 'Lato', Calibri, Arial, sans-serif;
            border: none;
            border-radius: 3px;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
        }
        
        .flat-table th, .flat-table td {
            box-shadow: inset 0 -1px rgba(0, 0, 0, 0.25),
            inset 0 1px rgba(0, 0, 0, 0.25);
        }
        
        .flat-table th {
            font-weight: normal;
            -webkit-font-smoothing: antialiased;
            padding: 1em;
            color: rgba(0, 0, 0, 0.45);
            text-shadow: 0 0 1px rgba(0, 0, 0, 0.1);
            font-size: 1.5em;
        }
        
        .flat-table td {
            color: #f7f7f7;
            padding: 0.7em 1em 0.7em 1.15em;
            text-shadow: 0 0 1px rgba(255, 255, 255, 0.1);
            font-size: 1.4em;
        }
        
        .flat-table tr {
            -webkit-transition: background 0.3s, box-shadow 0.3s;
            -moz-transition: background 0.3s, box-shadow 0.3s;
            transition: background 0.3s, box-shadow 0.3s;
        }
        
        .flat-table-1 {
            background: #336ca6;
        }
        
        .flat-table-1 tr:hover {
            background: rgba(0, 0, 0, 0.19);
        }
        
        .flat-table-2 tr:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .flat-table-2 {
            background: #f06060;
        }
        
        .flat-table-3 {
            background: #52be7f;
        }
        
        .flat-table-3 tr:hover {
            background: rgba(0, 0, 0, 0.1);
        }
    </style>
</head>


<body data-ng-controller="EncodePresentCtrl">

<div style="margin: auto; width: 80%;">
    <h1 class="rsm-title-bar">
        <img src="https://redstonemail.com/companies/renderLogo/568ae6c2103fb4f64f2337d2" alt="logo" width="150px">
        <?= $appName ?>
    </h1>
    
    <br>
    
    <!-- isset($php_action) ? $php_action : '' -->
    <form data-ng-submit="sf_toggleLoad()" name="rsmDownload" enctype="multipart/form-data"
          action="/redstone/tools/<?= $php_action ?? '' ?>" method="POST">
        <input type="file" name="csv_file" data-flex="60" style="margin-right: 6px;">
        <br><br>
        <input type="submit">
    </form>
    
    <br>
    
</div>

<div class="rsm-text-center">
    <h2>Upload CSV to compute Job Costing and Commission</h2>
    <p class="rsm-encode-check">
        <small>When the program completes a zipped folder will get downloaded.</small>
    </p>
</div>

<footer class="rsm-text-center rsm-mt">
    <h5>Redstone Automation v0.0.{{8-9+2}}</h5>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.6.8/angular.min.js"
        integrity="sha256-drxfSiYW94qA5Cyx5wrs8T8qV5GB45vES84r+X4zNS0="
        crossorigin="anonymous"></script>

<script>
    
    // _ANGULARJS APP
    let app = angular.module('rsm-comauto-self-service', []);
    app.controller('EncodePresentCtrl', [
        '$scope', '$interval', '$http', '$timeout', '$sce',
        EncodePresentCtrlClass
    ]);

    // _CLASS CONTROLLER
    function EncodePresentCtrlClass($scope, $interval, $http, $timeout, $sce) {

        $scope.s_status = 'Controller is wired up';
        $scope.s_interval = 0;
        /*
            angularjs_id: "ng3321592l"
            created_at: "2019-07-23 19:43:48.630"
            encode2: "227"
            first_field: "11078"
            id: "4490"
            rsm_column: "1"
            rsm_file_name: "21127fd3076cfb87"
            rsm_row: "1"
        */
        $scope.s_removedEncodes = null;
        $scope.s_showLoad = false;
        let isLocal = (window.location.hostname.indexOf('localhost') > -1);
        //-- AngularJS_id was a PHP computed value echoed out to a js var
        //let localUri = 'http://localhost/redstone/tools/get-removed-encodes/' + AngularJS_id;
        //let proUri = 'http://192.168.7.17/redstone/tools/get-removed-encodes/' + AngularJS_id;
        //let getRemovedEncodesUri = isLocal ? localUri : proUri;
        let makeRequest = true;

        // sf = scope function
        $scope.sf_getEncodeInfo = function ($event) {
            $scope.s_showLoad = false;

        };

        $scope.sf_toggleLoad = function() {
            $scope.s_showLoad = !$scope.s_showLoad;
        };

        $scope.sf_getEncodeInfoTimeout = function ($event) {
            //$event.preventDefault();
            console.log('AngularJS picked up click');
            console.log("The ngid from PHP = " + AngularJS_id);
            if (makeRequest) {
                $timeout(function () {
                    $http.get(getRemovedEncodesUri).then(function (res) {
                        let data = res.data;
                        console.log('The response from the redstone tools API = ', res);
                        if (data.length > 0) {
                            let htmlEncode;
                            let rec;
                            for (let i = 0; i < data.length; i++) {
                                rec = data[i];
                                htmlEncode = `<span>&#${rec.encode2};</span>`;
                                rec.encode = rec.encode2;
                                rec.encode2 = $sce.trustAsHtml(htmlEncode);
                            }
                        } else {
                            data = [];
                            data[0] = {
                                encode: 'empty',
                                rsm_column: 'empty',
                                rsm_row: 'empty',
                                first_field: 'empty'
                            };
                        }
                        $scope.s_removedEncodes = data;
                    }).catch(function (err) {
                        console.log('__>> ERROR - not able to GET request: ' + getRemovedEncodesUri, err);
                    }); // END OF: $http.get()
                }, 1500);
            }

        };

        /*
        // simple interval test to ensure app can maintain state
        $interval(function () {
            $scope.s_interval += 2.5;
        }, 500);
        */
    }
</script>

</body>
</html>


