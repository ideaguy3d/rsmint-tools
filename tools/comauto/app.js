angular.module('rsm-app',
    ['ngMaterial', 'ngRoute', 'ngMessages', 'ngMdIcons',
        'ngAnimate', 'users']) // , "material.svgAssetsCache"

// configure the md theme
    .config([
        "$mdThemingProvider", "$mdIconProvider",
        function ($mdThemingProvider, $mdIconProvider) {
            let redstoneRedMap = $mdThemingProvider.extendPalette('red', {
                '500': '#434343',
                'contrastDefaultColor': 'dark'
            });

            $mdThemingProvider.definePalette('redstoneRed', redstoneRedMap);
            $mdThemingProvider.theme('default').primaryPalette('redstoneRed');

            $mdIconProvider
                .defaultIconSet("./assets/svg/avatars.svg", 128)
                .icon("menu", "./assets/svg/menu.svg", 24)
                .icon("share", "./assets/svg/share.svg", 24)
                .icon("google_plus", "./assets/svg/google_plus.svg", 512)
                .icon("hangouts", "./assets/svg/hangouts.svg", 512)
                .icon("twitter", "./assets/svg/twitter.svg", 512)
                .icon("phone", "./assets/svg/phone.svg", 512);

            $mdThemingProvider.theme('default')
            //.primaryPalette('grey')
            //.accentPalette('red')
            //.backgroundPalette('red')
            //.dark()
            ;

        }])

    // configure the routes
    .config(['$routeProvider', '$locationProvider',
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
