<?php
if($_GET["game"] == "lucky-joker") $faucet = new \AllTheSatoshi\Faucet\CardsFaucet($mgr->address);
else if($_GET["game"] == "random-number") $faucet = new \AllTheSatoshi\Faucet\SpinnerFaucet($mgr->address);
else {
    ob_flush();
    header("Location: ./");
}

if($faucet->satoshi() < 1) {
    ob_flush();
    header("Location: ./" . $_GET["game"] . ".html");
}

?>
<div class="middle left">
    <?= $top_bar ?>
    <h2>Complete the captcha to collect your <?= $faucet->satoshi() | 0; ?> satoshi.</h2>
    <form action="javascript: void(0);" ng-submit="claim();" claim-form game="<?= $_GET["game"] ?>">
        <div id="acwidget">
            <script type="text/javascript"
                    src="http://api.solvemedia.com/papi/challenge.script?k=<?= \AllTheSatoshi\Util\Config::ini("captcha_services", "solveMediaPublicKey") ?>">
            </script>

            <noscript>
                <iframe src="http://api.solvemedia.com/papi/challenge.noscript?k=<?= \AllTheSatoshi\Util\Config::ini("captcha_services", "solveMediaPublicKey") ?>" height="300" width="500" frameborder="0"></iframe><br/>
                <textarea name="adcopy_challenge" rows="3" cols="40"></textarea>
                <input type="hidden" name="adcopy_response" value="manual_challenge"/>
            </noscript>
        </div>
        <a href="//adbit.co/?a=Advertise&b=View_Bid&c=TU5BRHOMMS3FI" target="_blank" style="margin: 0 auto;">&#8659; Your Ad Here &#8659;</a>
        <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('adbit','TU5BRHOMMS3FI'); ?></div><br/>
        <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('adbit','1VSG0O1G1JA3P'); ?></div>
        <a href="//adbit.co/?a=Advertise&b=View_Bid&c=1VSG0O1G1JA3P" target="_blank" style="margin: 0 auto;">&#8657; Your Ad Here &#8657;</a><br/><br/><br/>
        <input type="submit" value="Collect">
        <button onclick="window.history.back()">Cancel</button>
    </form>
</div>