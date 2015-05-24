<?php
$faucet = new \AllTheSatoshi\Faucet\CardsFaucet($mgr->address);

if(!$faucet->isReady()) {
    ob_flush();
    header("Location: ./wait-lucky-joker.html");
}

$revealed = $faucet->revealed;
$revealedjs = implode("','", $revealed == null ? [] : $revealed);
?>
<div class="middle left" ng-controller="LuckyJokerCtrl" ng-init="init([<?= $revealedjs == "" ? "" : "'" . $revealedjs . "'" ?>],<?= count($faucet->burnt) ?>,<?= round($faucet->getComboMultiplier($revealed), 1) ?>)">
    <?= $top_bar ?>
    <h1>Lucky Joker.</h1>
    Drag up to 5 cards to the green area to reveal.<br>
    You may also drag cards to the flame to burn them.<br>
    You get a multiplier for the cards you reveal and the combos you make.<br>
    Click collect when you are satisfied with your cards.<br>
    Hint: You don't have to reveal all 5 cards to get the most satoshi.<br><br><br>
    <div id="table">
        <div id="draw-deck">
            <div class="holder">
                <drawable-card ng-repeat="cardId in getRemainingCards() track by $index" style="position: absolute" ng-style="deckPos" card-id="{{cardId}}"/>
            </div>
        </div>
        <div id="burn-deck">
            <div class="holder">
                <drawable-card ng-repeat="cardId in getBurntCards() track by $index" style="position: absolute" ng-style="burnPos" card-id="cardId"/>
            </div>
        </div>
        <div style="clear:both;"></div>
    </div>
    <br>
    <p>
        Cards: {{countHand()}}&nbsp;&nbsp;&nbsp;&nbsp;Multiplier: {{getMultiplier()}}x&nbsp;&nbsp;&nbsp;&nbsp;Combo Multiplier: {{comboMultiplier}}x&nbsp;&nbsp;&nbsp;&nbsp;Satoshi: {{getSatoshiValue()}}
    </p>
    <br>
    <div id="hand">
        <div ng-repeat="cardId in revealedCards" class="card animate" ng-class="cardId"></div>
    </div>
    <br>
    <button onclick="location.href='./collect-lucky-joker.html'">Collect {{getSatoshiValue()}} Satoshi</button><br>
</div>