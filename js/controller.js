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

btcFaucetApp.run(function($rootScope) {});

btcFaucetApp.controller('MainContentCtrl', ['$scope', '$http', '$cookies', function($scope, $http, $cookies) {
    $scope.btcAddress = $cookies.btcAddress;
    $scope.satBalance = $cookies.satBalance;
    $scope.payout = function(paymentMethod) {
        $http.post("./ajax/payout.php",{'g-recaptcha-response': grecaptcha.getResponse(), utransserv: paymentMethod}).success(function(data) {
            var $form = $('<form method="post"></form>');
            $form.append($('<input>').attr("name", "event").val(data['success'] ? 'success' : 'error'));
            $form.append($('<input>').attr("name", "message").val(data['message']));
            $form.submit();
        }).error(function() {
            var $form = $('<form method="post"><input type="hidden" name="event" value="error"><input type="hidden" name="message" value="An error has occurred, the owner has been notified.">');
            $form.submit();
        });
    };
}]);
