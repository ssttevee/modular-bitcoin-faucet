btcFaucetApp.directive('faucetCooldown', function() {

    var link = function(scope, element, attrs) {
        scope.timeLeft = attrs.faucetCooldown;

        setInterval(function () {
            scope.timeLeft--;
            if(scope.timeLeft < 1) eval(attrs.onTimeUp);
            scope.$apply();
        }, 1000);

        scope.secondsToStr = function (seconds) {
            var hours = (seconds / 60 / 60) | 0;
            var mins = ((seconds - hours * 3600) / 60) | 0;
            var secs = (seconds - mins * 60 - hours * 3600);

            return (hours > 0 ? ('00' + hours).slice(-2) + ':' : '') + ('00' + mins).slice(-2) + ':' + ('00' + secs).slice(-2);
        };

        scope.goTo = function(url) {
            windows.location.href = url;
        };
    };


    return {
        restrict: 'A',
        template: '{{secondsToStr(timeLeft)}}',
        link: link
    }

});