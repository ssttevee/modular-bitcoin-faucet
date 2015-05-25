btcFaucetApp.directive('claimForm', ['$notice', function($notice) {

    var link = function(scope, element, attrs) {

        setInterval(function() {if($('#acwidget').children().length < 1) ACPuzzle.create('GFWOoXyYExNZgFBgTogSqX3Xgr.qUPWE', 'acwidget')}, 1000);

        scope.claim = function() {
            $.ajax("./ajax/" + attrs.game + "/claim.json", {
                method: "POST",
                dataType: "json",
                data: {
                    captcha_challenge: getFormValue(element, 'adcopy_challenge'),
                    captcha_response: getFormValue(element, 'adcopy_response')
                }
            }).done(function(json) {
                $form = $notice.getEventForm({
                    event: json.success ? "success" : "error",
                    message: json.message
                });
                if(json.success) $form.attr("action", "./wait-" + attrs.game + ".html");
                else $form.attr("action", "./" + attrs.game + ".html");
                $form.submit();
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