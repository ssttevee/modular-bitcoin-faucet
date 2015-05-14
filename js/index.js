btcFaucetApp.controller('SpinnerFaucetCtrl', ['$scope', '$http', '$notice', function($scope, $http, $notice) {

    $scope.init = function(lastSpin,formula,triesLeft,config) {
        $scope.lastSpin = lastSpin;
        $scope.formula = formula == '' ? 'fractal' : formula;
        $scope.remainingSpins = triesLeft;
        $scope.spinCfg = config;
        if(lastSpin != null) {
            $scope.spin(lastSpin);
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
        if(x == null) x = Math.random() * $scope.spinCfg.chance;
        angular.element(document.querySelector('#rng-spinner')).text(("000" + (x|0)).slice(-4));
        angular.element(document.querySelector('#rng-value')).text('= ' + $scope.getSatoshiValue(x | 0, $scope.formula) + " Satoshi");
        if($scope.spinningDown && $scope.spinDownCounter == 0) {
            clearInterval($scope.intervalId);
            $scope.spinDown();
        }
    };
    $scope.getSatoshiValue = function(x, formula) {
        var base = $scope.spinCfg.base;
        var max = $scope.spinCfg.max;
        var chance = $scope.spinCfg.chance;
        var formulas = {
            fractal: "base + (max + max/chance)/(x/5 + 1) - max/chance",
            radical: "max /= 20;base - Math.sqrt(max*max/chance*x) + max",
        };
        return eval(formulas[formula]) | 0;
    };
    $scope.spinDown = function() {
        if(!$scope.spinningDown) {
            $http.post("./ajax.php?action=spin", {curve:$scope.formula}).success(function(data) {
                if(!data.success) {
                    $notice.getEventForm({
                        event: 'error',
                        message: data['message'],
                    }).submit();
                } else {
                    $scope.number = data.spin;
                    $scope.remainingSpins = data.tries;
                }
            }).error(function(data) {
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
    $scope.claimSpin = function() {
        $http.post("./ajax.php?action=claim_spin",{claim:true,'g-recaptcha-response': grecaptcha.getResponse()}).success(function(data) {
            $notice.getEventForm({
                event: data['success'] ? 'success' : 'error',
                message: data['message'],
            }).submit();
        }).error(function(data) {
            console.log(data);
            $notice.getEventForm({
                event: 'error',
                message: 'An unknown error has occurred: ' + data,
            }).submit();
        });
    };
    $scope.stopSpin = function() {
        if(!$scope.spinningDown) $scope.spinDown();
    };
    $scope.startCountDown = function() {
        setInterval(function(){
            $scope.timeLeft--;
            if($scope.timeLeft < 1) location.reload(true);
            $scope.$apply();
        }, 1000);
    };
    $scope.secondsToStr = function(seconds) {
        var hours = (seconds / 60 / 60) | 0;
        var mins = ((seconds - hours*3600) / 60) | 0;
        var secs = (seconds - mins*60 - hours*3600);

        return (hours > 0 ? ('00' + hours).slice(-2) + ':' : '') + ('00' + mins).slice(-2) + ':' + ('00' + secs).slice(-2);
    };
}]);