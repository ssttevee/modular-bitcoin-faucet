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
        <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div><br>
        <input type="submit" value="Collect">
        <button onclick="location.href='./<?= $_GET["game"] ?>.html'">Cancel</button>
    </form>
</div>