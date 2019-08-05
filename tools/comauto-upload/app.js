
// _ANGULARJS APP
let app = angular.module('rsm-comauto-self-service', ['ngRoute']);
app.controller('EncodePresentCtrl', [
    '$scope', '$interval', '$http', '$timeout', '$sce',
    EncodePresentCtrlClass
]);


// configure the routes
app.config(['$routeProvider', '$locationProvider',
    function ($routeProvider, $locationProvider) {
        $routeProvider
            .when('/', {
                templateUrl: 'states/home/view.home.html',
                controller: 'HomeCtrl',
                controllerAs: 'homeCtrl'
            })
            // controller: 'CoreCtrl'
            .when('/business-intelligence', {
                templateUrl: 'states/bi/view.bi.html',
                controller: 'CoreCtrl',
                resolve: {
                    resolvedShowBusinessIntelligenceView: function () {
                        return true;
                    }
                }
            })
            // controller: 'InventoryCtrl'
            .when('/inventory', {
                templateUrl: 'states/inventory/view.inventory.html',
                controller: 'InventoryCtrl',
                controllerAs: 'cInv',
                resolve: {
                    resolvedAuthCheck: function () {
                        let test = "hi ^_^/";
                        return "'/inventory' route resolved value = " + test;
                    }
                }
            })
            // controller: 'InventoryCtrl'
            .when('/add-inventory', {
                templateUrl: 'states/inventory/view.add-inventory.html',
                controller: 'AddInvCtrl',
                controllerAs: 'cAddInv',
                resolve: {
                    resolvedAddInvAuthCheck: function () {
                        let test = "hi ^_^/";
                        return "'/add-inventory' route resolved value = " + test;
                    }
                }
            })
            // controller: 'RsmAuthCtrl'
            .when('/auth', {
                templateUrl: 'states/auth/view.auth.html',
                controller: 'RsmAuthCtrl'
            })
            // controller: '',
            .when('/encode-remove', {
                templateUrl: '',
                controller: 'EncodeRemoveCtrl'
            })
            // controller: 'RsmViewTestCtrl'
            .when('/test', {
                templateUrl: 'states/view-test/view.test.html',
                controller: 'RsmViewTestCtrl',
                controllerAs: 'cRsmViewTest'
            });

        // $locationProvider.otherwise('/');
    }
]);

app.controller('EncodePresentCtrl', [
    '$scope', '$interval', '$http', '$timeout', '$sce',
    EncodePresentCtrlClass
]);

// _CLASS CONTROLLER
function EncodePresentCtrlClass(
    $scope, $interval, $http, $timeout, $sce
) {
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

    $scope.sf_toggleLoad = function () {
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


//