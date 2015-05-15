btcFaucetApp.controller('LoginController', ['$scope', '$http', '$notice', function($scope, $http, $notice) {
    $scope.login = function() {
        $http.post("./ajax.php?action=login",{btcAddress:$scope.address}).success(function(data) {
            $notice.getEventForm({
                event: data['success'] ? 'success' : 'error',
                message: data['message'],
            }).submit();
        }).error(function(data) {
            console.log(data);
            $notice.getEventForm({
                event: 'error',
                message: 'An unknown error has occurred: ' + data,
            });
        });
    }
}]);