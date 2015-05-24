btcFaucetApp.directive('claimForm', ['$notice', function($notice) {

    var link = function(scope, element, attrs) {

        scope.claim = function() {
            var data = element.serializeArray();
            $.ajax({
                method: "POST",
                url: "./ajax/" + attrs.game + "/claim.json",
                data: {captcha_challenge: data.adcopy_challenge, captcha_response: data.adcopy_response}
            }).done(function(msg) {
                var json = JSON.parse(msg);
                $notice.getEventForm({
                    event: json.success ? "success" : "error",
                    message: json.message
                }).attr("action", "./" + attrs.game + ".html").submit();
            });
        }

    };

    return {
        restrict: 'A',
        link: link
    };
}]);