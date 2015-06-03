btcFaucetApp.directive('twentyFortyEight', [function() {

    var link = function(scope, element, attrs, ngModel) {
        ngModel.$setViewValue(0);

        var actuator = new HTMLActuator();
        var moving = false;

        var conn = new WebSocket('ws://' + window.location.hostname + ':8351');
        conn.onopen = function(e) {
            console.log("WebSocket connection established!");
            conn.send(JSON.stringify({op:"login",address:attrs.addr}));
            conn.onmessage = function(e) {
                console.log(JSON.parse(e.data).message);
                actuator = new HTMLActuator();
                conn.send(JSON.stringify({op:"start",module:"twenty-forty-eight"}));
                conn.onmessage = onmessage;
            };
        };

        var onmessage = function(e) {
            var data = JSON.parse(e.data);
            ngModel.$setViewValue(data.score);
            actuator.actuate(data.grid, {
                over: data.gameover
            });
            moving = false;
        };

        $(document).on("keydown", function(e) {
            if(moving) return;
            var keys = [38, 39, 40, 37]; // up, right, down, left
            for(var i = 0; i < keys.length; i++) {
                if(e.which == keys[i]) {
                    e.preventDefault();
                    conn.send(JSON.stringify({op:"move",module:"twenty-forty-eight",params:[i]}));
                    moving = true;
                }
            }
        });

    };

    return {
        restrict: 'C',
        replace: true,
        require: '?ngModel',
        templateUrl: './assets/twenty-forty-eight/game-container-template.html',
        link: link
    };
}]);