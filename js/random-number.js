btcFaucetApp.controller('SpinnerFaucetCtrl', ['$scope', '$http', '$notice', function($scope, $http, $notice) {

    $scope.$watch('showCaptcha', function(newValue, oldValue) {
        if(newValue) ACPuzzle.create('GFWOoXyYExNZgFBgTogSqX3Xgr.qUPWE', 'acwidget');
        else ACPuzzle.destroy();
    });

    $scope.init = function(lastSpin,formula,triesLeft,config) {
        $scope.lastSpin = lastSpin;
        $scope.number = lastSpin;
        $scope.formula = formula == '' ? 'fractal' : formula;
        $scope.remainingSpins = triesLeft;
        $scope.spinCfg = config;
        if(lastSpin != null) {
            $scope.tempNumber = lastSpin;
            $scope.spinningDown = true;
            $scope.spinDownDone = true;
            $scope.spinDownCounter = 10;
        }
        if(triesLeft < 1) {

        }
        delete $scope.init;
    };

    $scope.startSpin = function() {
        if($scope.lastSpin != null) return;
        $scope.intervalId = setInterval($scope.spin, 100);
        $scope.spinningDown = false;
        $scope.spinDownDone = false;
    };
    $scope.spin = function(x) {
        $scope.tempNumber = x == null ? Math.random() * $scope.spinCfg.chance : x;
        $scope.$apply();
        if($scope.spinningDown && $scope.spinDownCounter == 0) {
            clearInterval($scope.intervalId);
            $scope.spinDown();
        }
    };
    $scope.getSpinnerText = function(x) {
        return ("000" + (x|0)).slice(-4);
    };
    $scope.getSatoshiValue = function(x, formula) {
        var base = $scope.spinCfg.base;
        var max = $scope.spinCfg.max;
        var chance = $scope.spinCfg.chance;
        var formulas = {
            fractal: "base + (max + max/chance)/(x/25 + 1) - max/chance",
            radical: "max /= 20;base - Math.sqrt(max*max/chance*x) + max",
        };
        return eval(formulas[formula]) | 0;
    };
    $scope.spinDown = function() {
        if(!$scope.spinningDown) {
            $.ajax("./ajax/random-number/spin.json", {
                method: "POST",
                dataType: "json",
                data: {
                    curve: $scope.formula
                }
            }).done(function(data) {
                if(!data.success) {
                    $notice.getEventForm({
                        event: 'error',
                        message: data['message'],
                    }).submit();
                } else {
                    $scope.number = data.spin;
                    $scope.remainingSpins = data.tries;
                }
            }).fail(function(data) {
                console.log(data);
                $notice.getEventForm({
                    event: 'error',
                    message: 'An unknown error has occurred: ' + data,
                }).submit();
            });
            $scope.spinDownCounter = 0;
            $scope.spinningDown = true;
        } else {
            $scope.spinDownCounter++;
            $scope.spin();
            if ($scope.spinDownCounter < 8 || $scope.number == null) {
                setTimeout($scope.spinDown, 100 + $scope.spinDownCounter * 100 / 2);
            } else {
                setTimeout(function() {
                    $scope.spin($scope.number);
                    $scope.spinDownDone = true;
                    $scope.$apply();
                }, 100 + $scope.spinDownCounter * 100 / 2);
            }
        }
    };
    $scope.stopSpin = function() {
        if(!$scope.spinningDown) $scope.spinDown();
    };
}]);