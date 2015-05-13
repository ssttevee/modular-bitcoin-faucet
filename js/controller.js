var btcFaucetApp = angular.module('btcFaucetApp',['ngCookies'], function($httpProvider) {
    // Use x-www-form-urlencoded Content-Type
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';

    /**
     * The workhorse; converts an object to x-www-form-urlencoded serialization.
     * @param {Object} obj
     * @return {String}
     */
    var param = function(obj) {
        var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

        for(name in obj) {
            value = obj[name];

            if(value instanceof Array) {
                for(i=0; i<value.length; ++i) {
                    subValue = value[i];
                    fullSubName = name + '[' + i + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += param(innerObj) + '&';
                }
            }
            else if(value instanceof Object) {
                for(subName in value) {
                    subValue = value[subName];
                    fullSubName = name + '[' + subName + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += param(innerObj) + '&';
                }
            }
            else if(value !== undefined && value !== null)
                query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
        }

        return query.length ? query.substr(0, query.length - 1) : query;
    };

    // Override $http service's default transformRequest
    $httpProvider.defaults.transformRequest = [function(data) {
        return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
    }];
});

btcFaucetApp.controller('MainFaucetCtrl', ['$scope', '$http', '$cookies', function($scope, $http, $cookies) {
    $scope.btcAddress = $cookies.btcAddress;
    $scope.satBalance = $cookies.satBalance;
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
        if(x == null) x = Math.floor(Math.random() * $scope.spinCfg.chance);
        console.log(x);
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
            $http.post("./ajax/spin.php", {curve:$scope.formula}).success(function(data) {
                if(!data.spin) {
                    angular.element(document.querySelector('#rng-spinner')).text('zzzz');
                    angular.element(document.querySelector('#rng-value')).text('You\'ve run out of tries');
                } else {
                    $scope.number = data.spin;
                    $scope.remainingSpins = data.tries;
                }
            }).error(function(data) {
                console.log(data);
                angular.element(document.querySelector('#rng-spinner')).text('X.X');
                angular.element(document.querySelector('#rng-value')).text('Something went wrong...');
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
        $http.post("./ajax/spin.php",{claim:true,'g-recaptcha-response': grecaptcha.getResponse()}).success(function(data) {
            var $form = $('<form method="post"><input type="hidden" name="event" value="satoshiclaimed"><input type="hidden" name="amount" value="' + data['added'] + '"></form>');
            if(data['added'] == null) {
                $form.append($('<input type="hidden" name="error" value="Nothing to claim">'));
            }
            $form.submit();
        }).error(function(data) {
            console.log(data);
            angular.element(document.querySelector('#rng-spinner')).text('X.X');
            angular.element(document.querySelector('#rng-value')).text('Something went wrong...');
        });
    };
    $scope.stopSpin = function() {
        if(!$scope.spinningDown) $scope.spinDown();
    };
    $scope.payout = function() {
        $http.post("./ajax/payout.php",{'g-recaptcha-response': grecaptcha.getResponse(), utransserv: $scope.paymentMethod}).success(function(data) {
            var $form = $('<form method="post"><input type="hidden" name="event" value="' + (data['success'] ? 'success' : 'error') + '"><input type="hidden" name="message" value="' + data['message'] + '">');
            //$form.submit();
        }).error(function() {
            var $form = $('<form method="post"><input type="hidden" name="event" value="error"><input type="hidden" name="message" value="An error has occurred, the owner has been notified.">');
            $form.submit();
        });
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