<?php
$faucet = new \AllTheSatoshi\Faucet\SpinnerFaucet($mgr);
?>
<div class="middle left" ng-controller="SpinnerFaucetCtrl" ng-init="init(<?= $faucet->lastNumber ?>, '<?= $faucet->curve ?>', <?= $faucet->tries_left ?>, {base: <?= $faucet->_cfg("baseAmt") ?>, max: <?= $faucet->_cfg("maxBonusAmt") ?>, chance: <?= $faucet->_cfg("bonusChance") ?>})">
    <?= $top_bar ?>
    <?php if($faucet->getRemainingTries() < 1 && $mgr->lastSpin["number"] == null) { ?>
        <h2>Time until next claim</h2>
        <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div>
        <div ng-init="timeLeft = <?= $faucet->getWaitTime() ?>;startCountDown();">
            <h1>{{secondsToStr(timeLeft)}}</h1>
        </div>
    <?php } else { ?>
        <h1>Welcome to All The Satoshi Beta Faucet.</h1>
        Scroll down and press "Stop" to get a random number.<br>
        The lower your number is, the more satoshi you will get.<br>
        The fractal curve will give you a low chance to get up to 2000 satoshi.<br>
        The radial curve will give you a high chance to get up to 250 satoshi.<br>
        <br>
        Pick a curve:
        <div id="curve-selector">
            <input id="fractal-formula" type="radio" name="formula" value="fractal" ng-model="formula" ng-disabled="spinningDown"><label for="fractal-formula">Fractal</label>
            <input id="radical-formula" type="radio" name="formula" value="radical" ng-model="formula" ng-disabled="spinningDown"><label for="radical-formula">Radical</label>
        </div>
        <div id="rng-spinner" ng-init="startSpin();">{{getSpinnerText(tempNumber)}}</div>
        <div>= {{getSatoshiValue(tempNumber, formula)}} satoshi</div>
        <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div><br>
        <button id="rng-stop" ng-click="stopSpin()" ng-hide="spinningDown">Stop</button>
        <span ng-show="spinDownDone">{{remainingSpins}} tries left<br></span>
        <button id="rng-respin" ng-click="lastSpin = null;number = null;startSpin()" ng-show="spinDownDone" ng-disabled="remainingSpins < 1">Try Again</button>
        <button id="rng-claim" ng-click="showCaptcha = true;captchaShowClaim = true;" ng-show="spinDownDone">Claim</button><br><br>
    <?php } ?>
    <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('bitclix','11724'); ?></div>
    <h2>Refer and get 10% of every dispense!</h2>
    Your referral link:<br>
    <input id="ref-url" type="text" value="<?= $base_url ?>?r={{btcAddress}}" onclick="$(this).select();">
    <div id="captcha-container" ng-show="showCaptcha">
        <h3 ng-show="captchaShowClaim">Complete the captcha to claim your {{getSatoshiValue(number, formula)}} satoshi</h3>
        <h1 ng-show="captchaShowPayout">Payout to {{paymentMethod}}</h1>
        <div id="acwidget"></div>
        <a href="//adbit.co/?a=Advertise&b=View_Bid&c=TU5BRHOMMS3FI" target="_blank" style="margin: 0 auto;">&#8659; Your Ad Here &#8659;</a>
        <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('adbit','TU5BRHOMMS3FI'); ?></div><br/>
        <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('adbit','1VSG0O1G1JA3P'); ?></div>
        <a href="//adbit.co/?a=Advertise&b=View_Bid&c=1VSG0O1G1JA3P" target="_blank" style="margin: 0 auto;">&#8657; Your Ad Here &#8657;</a><br/><br/><br/>
        <button id="rng-claim" ng-click="claimSpin()" ng-show="captchaShowClaim">Claim</button>
        <button id="rng-claim" ng-click="payout(paymentMethod)" ng-show="captchaShowPayout">Payout</button>
        <button id="rng-claim" ng-click="showCaptcha = false;captchaShowClaim = false;captchaShowPayout = false;">Cancel</button>
    </div>
</div>