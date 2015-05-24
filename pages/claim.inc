<?php
if($_GET["game"] == "lucky-joker") $faucet = new \AllTheSatoshi\Faucet\CardsFaucet($mgr->address);

?>
<div class="middle left">
    <?= $top_bar ?>
    <h2>Complete the captcha to collect your <?= $fuacet->satoshi(); ?> satoshi</h2>
    <h1 ng-show="captchaShowPayout">Payout to {{paymentMethod}}</h1>
    <div id="acwidget"></div>
    <a href="//adbit.co/?a=Advertise&b=View_Bid&c=TU5BRHOMMS3FI" target="_blank" style="margin: 0 auto;">&#8659; Your Ad Here &#8659;</a>
    <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('adbit','TU5BRHOMMS3FI'); ?></div><br/>
    <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('adbit','1VSG0O1G1JA3P'); ?></div>
    <a href="//adbit.co/?a=Advertise&b=View_Bid&c=1VSG0O1G1JA3P" target="_blank" style="margin: 0 auto;">&#8657; Your Ad Here &#8657;</a><br/><br/><br/>
    <button id="rng-claim" ng-click="claimSpin()" ng-show="captchaShowClaim">Collect</button>
    <button id="rng-claim" onclick="window.history.back()">Cancel</button>
</div>