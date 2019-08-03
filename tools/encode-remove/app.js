// isset($ngid) ? $ngid : ''
let AngularJS_id = "<?= $ngid ?? null ?>";
window.onload = function () {
    // e.g. /redstone/tools/?angularjs-id=ng488641789g
    //document.rsmDownload.action = rsm_action();
    console.log('window loaded');
};

function rsm_action(form) {
    const bil = 9000000000;
    const rand = Math.floor((Math.random() * bil) + 1);
    const ng = "angularjsphpsqlcompsciwebapp";
    const rLetter = ng.charAt(Math.floor((Math.random() * ng.length) + 1));
    const ngid = `ng${rand}${rLetter}`;
    const phpAction = "/redstone/tools/?angularjs-id=" + ngid;
    console.log("phpAction = " + phpAction);
    form.action = phpAction;
    //return phpAction;
}

// _ANGULARJS APP
let app = angular.module('rsm-encode-remove', []);
app.controller('EncodePresentCtrl', [
    '$scope', '$interval', '$http', '$timeout', '$sce',
    EncodePresentCtrlClass
]);

// _CLASS CONTROLLER
function EncodePresentCtrlClass($scope, $interval, $http, $timeout, $sce) {

    $scope.s_status = 'Controller wired up from another file';
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
    let localUri = 'http://localhost/redstone/tools/get-removed-encodes/' + AngularJS_id;
    let proUri = 'http://192.168.7.17/redstone/tools/get-removed-encodes/' + AngularJS_id;
    let getRemovedEncodesUri = isLocal ? localUri : proUri;
    let makeRequest = true;

    // sf = scope function
    $scope.sf_getEncodeInfo = function ($event) {
        $scope.s_showLoad = false;

        //$event.preventDefault();
        $http.get(getRemovedEncodesUri)
            .then(function (res) {
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
                }
                else {
                    data = [];
                    data[0] = {
                        encode: 'empty',
                        rsm_column: 'empty',
                        rsm_row: 'empty',
                        first_field: 'empty'
                    };
                }
                $scope.s_removedEncodes = data;

            })
            .catch(function (err) {
                console.log('__>> ERROR - not able to GET request: ' + getRemovedEncodesUri, err);
            }); // END OF: $http.get()

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
