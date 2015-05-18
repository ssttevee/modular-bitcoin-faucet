btcFaucetApp.controller('StatisticsController', ['$scope', function($scope) {
    $scope.getSatoshiValueByCurve = function(x, formula) {
        var base = 50;
        var max = 2000;
        var chance = 8000;
        var formulas = {
            fractal: "base + (max + max/chance)/(x/25 + 1) - max/chance",
            radical: "max /= 20;base - Math.sqrt(max*max/chance*x) + max",
        };
        return eval(formulas[formula]) | 0;
    };
}]);