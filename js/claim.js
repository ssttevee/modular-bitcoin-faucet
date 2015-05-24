btcFaucetApp.directive('claimForm', ['$notice', function($notice) {

    var link = function(scope, element, attrs) {

        scope.claim = function() {
            $.ajax("./ajax/" + attrs.game + "/claim.json", {
                method: "POST",
                dataType: "json",
                data: {
                    captcha_challenge: getFormValue(element, 'adcopy_challenge'),
                    captcha_response: getFormValue(element, 'adcopy_response')
                }
            }).done(function(json) {
                $notice.getEventForm({
                    event: json.success ? "success" : "error",
                    message: json.message
                }).attr("action", "./" + attrs.game + ".html").submit();
            });
        };

        var getFormValue = function(element, fieldName) {
            var data = element.serializeArray();
            for(var key in data)
                if(data[key]['name'] == fieldName) return data[key]['value'];
        };

    };

    return {
        restrict: 'A',
        link: link
    };
}]);