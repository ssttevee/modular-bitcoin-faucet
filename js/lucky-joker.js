btcFaucetApp.directive('drawableCard', [ function() {

    var link = function(scope, element, attrs) {

        element.on('mousedown', function(e) {

            $(document).on('mouseup', mouseup);

            element.removeClass('animate');
            scope.$emit('cardLift', [e, element]);
        });

        var mouseup = function(e) {
            $(document).off('mouseup');

            element.addClass('animate');
            scope.$emit('cardDrop', [e, element]);
        };
    };

    return {
        restrict: 'E',
        replace: true,
        template: '<div class="card red-back animate"></div>',
        link: link
    };
}]);

btcFaucetApp.controller('LuckyJokerCtrl', ['$scope', '$http', '$notice', function($scope, $http, $notice) {
    $scope.revealedCards = [];
    $scope.burntCards = 0;
    $scope.comboMultiplier = 1;

    var lastX, lastY;
    var isDragging = false;

    var offset = ($holder = $('#draw-deck .holder')).offset(), parent = $holder.offsetParent().offset(), $holder;
    $scope.deckPos = {left: offset.left - parent.left, top: offset.top - parent.top};
    offset = ($holder = $('#burn-deck .holder')).offset(), parent = $holder.offsetParent().offset();
    $scope.burnPos = {left: offset.left - parent.left, top: offset.top - parent.top};

    $scope.getRemainingCards = function() {
        return new Array(53 - $scope.revealedCards.length - $scope.burntCards);
    };
    $scope.getBurntCards = function() {
        return new Array($scope.burntCards);
    };
    $scope.getMultiplier = function() {
        return 6 - $('#hand').children().length;
    };
    $scope.countHand = function() {
        return $('#hand').children().length;
    };
    $scope.getSatoshiValue = function() {
        var amount = 0;
        for(var card in $scope.revealedCards) {
            var num = $scope.revealedCards[card].substring(1);
            amount += (num == "A" ? 14 : (num == "T" ? 10 : (num == "J" ? 11 : (num == "Q" ? 12 : (num == "K" ? 13 : (num == "oker" ? 26 : parseInt(num) ) ) ) ) ) );
        }
        return (amount * (6 - $scope.revealedCards.length) * $scope.comboMultiplier) | 0;
    };

    $scope.init = function(revealedCards, burntCards, comboMultiplier) {
        $scope.revealedCards = revealedCards;
        $scope.burntCards = burntCards;
        if(comboMultiplier != null) $scope.comboMultiplier = comboMultiplier;

        delete $scope.init;
    };

    $scope.$on('cardLift', function(evt, data) {
        var event = data[0], element = data[1];

        var pauseEvent = function(e){
            if(e.stopPropagation) e.stopPropagation();
            if(e.preventDefault) e.preventDefault();
            e.cancelBubble=true;
            e.returnValue=false;
            return false;
        };
        isDragging = true;

        pauseEvent(event || window.event);

        lastX = event.pageX;
        lastY = event.pageY;
        $(document).on('mousemove', function(e) {
            pauseEvent(e);
            element.css('left', "+=" + (e.pageX - lastX));
            element.css('top', "+=" + (e.pageY - lastY));

            var $hand = $('#hand'),$ghost = $hand.find('.ghost');
            if(isInsideElem(e, $hand)) {
                if ($ghost.length == 0 && $hand.children().length < 5) {
                    $ghost = $('<div class="card ghost animate"></div></div>');
                    $ghost.appendTo($hand);
                    $scope.$apply();
                }
            } else {
                if($ghost.length > 0) {
                    $ghost.remove();
                    $scope.$apply();
                }
            }

            lastX = e.pageX;
            lastY = e.pageY;
        });
    });
    $scope.$on('cardDrop', function(evt, data) {
        var event = data[0], element = data[1];
        var $snapTo, promise, animating = true;

        $(document).off('mousemove');

        if(isInsideElem(event, $snapTo = $('#burn-deck .holder'))) {
            draw('burn',function(p) {
                if(animating) promise = p;
                else promise();
            });
            var burnPos = getElemSnap($snapTo);
            element.removeAttr('drawable-card');
            element.css('left', "+=" + (burnPos.left - parseInt(element.css('left'))));
            element.css('top', "+=" + (burnPos.top - parseInt(element.css('top'))));
            element.off('mousedown');
            element.on(whichTransitionEvent(), function(e) {
                element.css("left", "");
                element.css("top", "");
                if(promise != null) promise();
                $scope.$apply();
            });
        } else if(isInsideElem(event, $snapTo = $('#hand')) && $scope.revealedCards.length < 5) {
            draw('reveal',function(p) {
                if(animating) promise = p;
                else promise();
            });
            var $ghost = $snapTo.find('.ghost');
            var ghostPos = getElemSnap($ghost);
            element.detach().appendTo($snapTo);
            element.removeAttr('drawable-card');
            element.css('left', "+=" + (ghostPos.left - parseInt(element.css('left'))));
            element.css('top', "+=" + (ghostPos.top - parseInt(element.css('top'))));
            element.off('mousedown');
            $ghost.remove();
            element.on(whichTransitionEvent(), function(e) {
                element.css("left", "");
                element.css("top", "");
                if(promise != null) promise();
                $scope.$apply();
            });
        } else {
            element.css('left', $scope.deckPos.left);
            element.css('top', $scope.deckPos.top);
        }
        $scope.$apply();
    });
    var draw = function(action, callback) {
        $http.post('./ajax/lucky-joker/' + action + '.json')
            .success(function(data) {
                callback(function() {
                    if (data['success']) {
                        $scope.revealedCards = data['revealed_cards'];
                        $scope.burntCards = data['burned_count'];
                        $scope.comboMultiplier = data['combo_multiplier'];
                    } else {
                        $('.middle').prependChild($('<div class="notice red">' + data['message'] + '</div>'));
                    }
                    if (data.hasOwnProperty('debug')) $('.middle').prependChild($('<div class="notice red">An error has occurred: ' + data['debug'] + '</div>'));
                });
            })
            .error(function(data) {
                console.log(data);
            });
    };
    var isInsideElem = function(e, elem) {
        var offset = elem.offset();
        var x = offset.left,
            y = offset.top,
            w = elem.width(),
            h = elem.height();

        return (x < e.pageX && x + w > e.pageX) && (y < e.pageY && y + h > e.pageY);
    };

    var getElemSnap = function(elem) {
        var deck = $(elem).offset();
        var parent = $(elem).offsetParent().offset();

        return {
            left: deck.left - parent.left,
            top: deck.top - parent.top
        };
    }
}]);
