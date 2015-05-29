<?php

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
    <?php if($faucet->isInGame()) { ?>
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
        <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('bitclix','11724'); ?></div>
        <p>
            Cards: {{countHand()}}&nbsp;&nbsp;&nbsp;&nbsp;Multiplier: {{getMultiplier()}}x&nbsp;&nbsp;&nbsp;&nbsp;Combo Multiplier: {{comboMultiplier}}x&nbsp;&nbsp;&nbsp;&nbsp;Satoshi: {{getSatoshiValue()}}
        </p>
        <div id="hand">
            <div ng-repeat="cardId in revealedCards" class="card animate" ng-class="cardId"></div>
        </div>
        <br>
        <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div>
        <br>
        <button onclick="location.href='./collect-lucky-joker.html'">Collect {{getSatoshiValue()}} Satoshi</button><br>
    <?php } else { ?>
        <div id="draw-deck" style="display: none;"><div class="holder"></div></div>
        <div id="burn-deck" style="display: none;"><div class="holder"></div></div>
        <p>How many times do you want to shuffle?</p>
        <input type="number" min="1" max="50" ng-model="shuffletimes" ng-init="shuffletimes = 5;"/><br><br>
        <button ng-click="startGame(shuffletimes);console.log(shuffletimes);">Start</button>
    <?php } ?>
</div>