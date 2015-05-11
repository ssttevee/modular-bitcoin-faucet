var btcFaucetApp = angular.module('btcFaucetApp',[]);

btcFaucetApp.controller('MainFaucetCtrl', function($scope) {
    $scope.startSpin = function() {
        $scope.intervalId = setInterval($scope.spin, 100)
        $scope.spinningDown = false;
    };
    $scope.spin = function() {
        var base = 50;
        var max = 2000;
        var chance = 8000;
        var x = Math.floor((Math.random() * chance));
        angular.element(document.querySelector('#rng-spinner')).text(("000" + x).slice(-4));
        angular.element(document.querySelector('#rng-value')).text('= ' + $scope.getSatoshiValue(x, base, max, chance, 'radical') + " Satoshi");
        if($scope.spinningDown && $scope.spinDownCounter == 0) {
            clearInterval($scope.intervalId);
            $scope.spinDown();
        }
    };
    $scope.getSatoshiValue = function(x, base, max, chance, formula) {
        var formulas = {
            fractal: "base + (max + max/chance)/(x/2 + 1) - max/chance",
            radical: "max /= 20;base - Math.sqrt(max*max/chance*x) + max",
        };
        return eval(formulas[formula]) | 0;
    };
    $scope.spinDown = function() {
        if(!$scope.spinningDown) {
            $scope.spinDownCounter = 0;
            $scope.spinningDown = true;
        } else {
            $scope.spinDownCounter++;
            $scope.spin();
            if ($scope.spinDownCounter < 10) {
                setTimeout($scope.spinDown, 100 + $scope.spinDownCounter * 100 / 2);
            } else {
                // do ajax thing here
            }
        }
    };
    $scope.stopSpin = function() {
        if(!$scope.spinningDown) $scope.spinDown();
    };
});