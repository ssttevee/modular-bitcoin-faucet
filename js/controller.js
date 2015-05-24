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

btcFaucetApp.service('$notice', function() {
    this.getEventForm = function(fields) {
        var $form = $('<form method="post"></form>');
        for(var name in fields) {
            $form.append($('<input>').attr("name", name).val(fields[name]));
        }
        return $form;
    };
});

btcFaucetApp.controller('MainContentCtrl', ['$scope', '$http', '$cookies', '$notice', function($scope, $http, $cookies, $notice) {
    $scope.btcAddress = $cookies.btcAddress;
    $scope.satBalance = $cookies.satBalance;
    $scope.payout = function(paymentMethod) {
        $http.post("./ajax.php?action=payout",  {'captcha_challenge': ACPuzzle.get_challenge(),'captcha_response': ACPuzzle.get_response(), utransserv: paymentMethod}).success(function(data) {
            $notice.getEventForm({
                event: data['success'] ? 'success' : 'error',
                message: data['message'],
            }).submit();
        }).error(function(data) {
            $notice.getEventForm({
                event: 'error',
                message: 'An unknown error has occurred: ' + data,
            }).submit();
        });
    };
}]);

/* From Modernizr */
function whichTransitionEvent(){
    var t;
    var el = document.createElement('fakeelement');
    var transitions = {
        'transition':'transitionend',
        'OTransition':'oTransitionEnd',
        'MozTransition':'transitionend',
        'WebkitTransition':'webkitTransitionEnd'
    }

    for(t in transitions){
        if( el.style[t] !== undefined ){
            return transitions[t];
        }
    }
}