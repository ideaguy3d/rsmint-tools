<?php

return function(string $uploadedFile = "[]", string $arFacDist = "[]"): void { ?>

    <html lang="en" data-ng-app="pmodApp">

    <head>
        <meta charset="utf-8">
        <title>Redstone Mint</title>

        <link rel="stylesheet" href="./vendor/vendor.css">
        <link rel="stylesheet" href="./redstone.css">

        <!-- ng-cloak -->
        <style type="text/css">
            [ng\:cloak], [ng-cloak], [data-ng-cloak], [x-ng-cloak], .ng-cloak, .x-ng-cloak {
                display: none !important;
            }

            a {
                text-decoration: none;
            }

            .rsm-round-btn.md-raised {
                border-radius: 19px !important;
                padding: 2px 10px !important;
            }
        </style>
    </head>

    <body class="ng-cloak" data-ng-controller="CoreCtrl" data-layout="column">

    <div data-layout="row" data-layout-align="center center">
        <img id='rsm-loader-gif' src='redstone.png' alt='loading' width="150px">
        <h1> &nbsp; Facility Distribution app</h1>
        <a href="/">
            <md-button class="md-raised md-primary rsm-round-btn">
                <span style="color: #ffffff">Redstone Mint</span>
            </md-button>
        </a>
    </div>

    <section>
        <div data-layout="row" data-layout-align="center center" data-layout-margin>
            <!--
            <div data-flex style="background-color: #434343;">
                <div data-layout="column">
                    <iframe src="/gmap1" frameborder="0" data-flex></iframe>
                </div>
            </div>
            -->

            <iframe data-ng-src="{{ccRsmMapUri}}" frameborder="0" width="100%" height="700px"></iframe>

            <div data-flex="60">
                <!-- CSV upload form -->
                <div data-ng-if="ccHasFile">
                    <form action="./">
                        <h3 class="rsm-download-fac-file">
                            <!-- Download file button -->
                            <a href="fac-download.php" target="_blank">
                                <md-button class="md-raised md-warn rsm-round-btn">
                                    Download File
                                </md-button>
                            </a>

                            <span> With [FAC] (facility) Zones Appended</span>
                        </h3>
                        <h3 class="rsm-upload-another-file">
                            <span> &nbsp; Or </span>

                            <!-- Start over button -->
                            <a href="./">
                                <md-button class="md-raised rsm-round-btn">
                                    Upload Another File
                                </md-button>
                            </a>
                        </h3>
                    </form>
                </div>

                <div data-ng-if="!ccHasFile">
                    <form action="./" method="POST" enctype="multipart/form-data">
                        <h2 class="rsm-input-header">
                            <input type="file" name="job-recs" data-flex="60" style="margin-right: 6px;">
                            <input type="submit" data-ng-click="ccPageRefresh()" data-flex>
                        </h2>
                    </form>
                    <p class="rsm-large-file-notice">
                        <i class="rsm-italic-font">
                            <small>Large files may take up to 10 seconds to process so please be patient</small>
                        </i>
                    </p>
                </div>

                <hr>

                <!-- to see files in folder rendered in browser
                    <p>Logs: ?php echo $logs ?></p>
                -->

                <!-- Data Output -->
                <table>
                    <thead>
                    <tr>
                        <td>Mailing Distribution</td>
                        <td>Mailers</td>
                    </tr>
                    </thead>

                    <tbody>
                    <tr data-ng-repeat="(key, value) in ccFacDistResults.mailing_distribution">
                        <td>{{key}}</td>
                        <td>{{value | number}}</td>
                    </tr>
                    </tbody>
                </table>

                <table>
                    <thead>
                    <tr>
                        <td>PMOD Zones</td>
                        <td>Mailers</td>
                    </tr>
                    </thead>

                    <tbody>
                    <tr data-ng-repeat="(key, value) in ccFacDistResults.pmod_zones">
                        <td>{{key}}</td>
                        <td>{{value | number}}</td>
                    </tr>
                    </tbody>
                </table>

                <!-- output to ensure Data controller is wired up to view -->
                <h1 style="background-color: #000000; color: #ffffff; font-family: Consolas, sans-serif;
                        text-align: center; padding: 4px;">
                    <b>{{ccStatus}}</b>
                </h1>
            </div>
        </div>
    </section>

    <!-- Bottom Footer / Copyright -->
    <section>
        <div data-layout="row" data-layout-align="center center">
            <p> Redstone Print & Mail INC. &copy; 2019 </p>
        </div>
    </section>

    <!-- vendor js -->
    <script src="./vendor/vendor.js"></script>

    <!-- custom js -->
    <script type="text/javascript">
        angular
            .module('pmodApp', [
                'firebase', 'ngAnimate', 'ngMaterial'
            ])
            .config(['$mdThemingProvider', function ($mdThemingProvider) {
                'use strict';

                let redstoneRedMap = $mdThemingProvider.extendPalette('red', {
                    '500': '#434343',
                    'contrastDefaultColor': 'dark'
                });

                $mdThemingProvider.definePalette('redstoneRed', redstoneRedMap);
                $mdThemingProvider.theme('default').primaryPalette('redstoneRed');
                $mdThemingProvider.theme('default');

                console.log('__>> AngularJS should be wired up and working');
            }])
            .controller('CoreCtrl', ['$scope', function ($scope) {
                //angular.element('#rsm-loader-gif').hide();
                $scope.ccStatus = 'Redstone Automation';
                $scope.ccUploadedFile = <?= $uploadedFile ?>;
                $scope.ccFacDistResults = <?= $arFacDist ?>;
                $scope.ccHasFile = (Object.keys($scope.ccUploadedFile).length > 0);
                $scope.ccFacDistArray = [];
                let rsmMapUri = '/fac-dist-map';
                if ($scope.ccHasFile) {
                    rsmMapUri = `/fac-dist-map/?sac=${$scope.ccFacDistResults.mailing_distribution.SAC}` +
                        `&atl=${$scope.ccFacDistResults.mailing_distribution.ATL}` +
                        `&dal=${$scope.ccFacDistResults.mailing_distribution.DAL}` +
                        `&den=${$scope.ccFacDistResults.mailing_distribution.DEN}`;
                } else {
                    rsmMapUri = `/fac-dist-map/?sac=0&atl=0&dal=0&den=0`;
                }
                $scope.ccRsmMapUri = rsmMapUri;

                let count = 0;

                console.log('__>> uploaded file = ', $scope.ccUploadedFile);
                console.log('__>> Fac Dist Results = ', $scope.ccFacDistResults);

                function sortByCount() {
                    // _SORT I need to sort each node by count
                    if (Object.keys($scope.ccFacDistResults).length > 0) {
                        for (let distribution in $scope.ccFacDistResults) {
                            if ($scope.ccFacDistResults.hasOwnProperty(distribution)) {
                                let distObj = $scope.ccFacDistResults[distribution];
                                for (let mailing in distObj) {
                                    let rec = $scope.ccFacDistResults[distribution];
                                    if (distObj.hasOwnProperty(mailing)) {
                                        let mk = mailing.toString();
                                        let recTemp = {};
                                        recTemp[mk] = rec[mk];
                                        $scope.ccFacDistArray[count] = recTemp;
                                    }
                                }
                            }
                        }
                    } else {
                        console.log('__>> ERROR - PHP did not properly do the Facility Distribution');
                    }
                }

                // refresh the page... but this happens to quickly for
                //PHP to pick up  the new file upload
                $scope.ccRefreshPage = function () {
                    console.log('__>> The page can be optionally reloaded here by JavaScript');
                    //location.reload();
                }
            }]);
    </script>

    </body>

    </html>

<?php } ?>
