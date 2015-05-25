btcFaucetApp.directive('payoutForm', ['$notice', function($notice) {

    var link = function(scope, element, attrs) {

        scope.claim = function() {
            $.ajax("./ajax.php?action=payout", {
                method: "POST",
                dataType: "json",
                data: {
                    utransserv: getFormValue(element, 'microwallet_service'),
                    captcha_challenge: getFormValue(element, 'adcopy_challenge'),
                    captcha_response: getFormValue(element, 'adcopy_response')
                }
            }).done(function(json) {
                $notice.getEventForm({
                    event: json.success ? "success" : "error",
                    message: json.message
                }).attr("action", "./").submit();
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