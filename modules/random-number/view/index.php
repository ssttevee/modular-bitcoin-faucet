<?php

if(!$faucet->isReady()) {
    ob_flush();
    header("Location: ./wait-random-number.html");
}

$revealed = $faucet->revealed;
$revealedjs = implode("','", $revealed == null ? [] : $revealed);
?>
<div class="middle left" ng-controller="SpinnerFaucetCtrl" ng-init="init(<?= $faucet->number == null ? 'null' : $faucet->number ?>, '<?= $faucet->curve ?>', <?= $faucet->tries_left ?>, {base: <?= $faucet->_cfg("baseAmt") ?>, max: <?= $faucet->_cfg("maxBonusAmt") ?>, chance: <?= $faucet->_cfg("bonusChance") ?>})">
    <?= $top_bar ?>
    <h1>Random Number Generator.</h1>
    Scroll down and press "Stop" to get a random number.<br>
    The lower your number is, the more satoshi you will get.<br>
    The fractal curve will give you a low chance to get up to 2000 satoshi.<br>
    The radial curve will give you a high chance to get up to 250 satoshi.<br>
    <br>
    Pick a curve:
    <div id="curve-selector">
        <input id="fractal-formula" type="radio" name="formula" value="fractal" ng-model="formula" ng-disabled="spinningDown"><label for="fractal-formula">Fractal</label>
        <input id="radical-formula" type="radio" name="formula" value="radical" ng-model="formula" ng-disabled="spinningDown"><label for="radical-formula">Radical</label>
    </div><br>
    <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('bitclix','11724'); ?></div>
    <div id="rng-spinner" ng-init="startSpin();">{{getSpinnerText(tempNumber)}}</div>
    <div>= {{getSatoshiValue(tempNumber, formula)}} satoshi</div>
    <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div><br>
    <button ng-click="stopSpin()" ng-hide="spinningDown">Stop</button>
    <span ng-show="spinDownDone">{{remainingSpins}} tries left<br></span>
    <button ng-click="lastSpin = null;number = null;startSpin()" ng-show="spinDownDone" ng-disabled="remainingSpins < 1">Try Again</button>
    <button onclick="location.href='./collect-random-number.html'" ng-show="spinDownDone">Collect {{getSatoshiValue(tempNumber, formula)}} Satoshi</button><br>
</div>