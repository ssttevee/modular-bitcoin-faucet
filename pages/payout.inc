<div class="middle left" ng-init="mwalletservice='paytoshi'">
    <?= $top_bar ?>
    <h2>Complete the captcha to withdraw <?= $mgr->getBalance(); ?> satoshi. </h2>
    <form action="javascript: void(0);" ng-submit="claim();" payout-form>
        <div>
            <input id="paytoshi" type="radio" name="microwallet_service" value="paytoshi" ng-model="mwalletservice" ng-disabled="spinningDown"><label for="paytoshi">Paytoshi</label>
            <input id="faucetbox" type="radio" name="microwallet_service" value="faucetbox" ng-model="mwalletservice" ng-disabled="spinningDown"><label for="faucetbox">FaucetBOX</label>
        </div>
        <div id="acwidget">
            <script type="text/javascript"
                    src="http://api.solvemedia.com/papi/challenge.script?k=<?= \AllTheSatoshi\Util\Config::ini("captcha_services", "solveMediaPublicKey") ?>">
            </script>
        </div>
        <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div><br>
        <input type="submit" value="Withdraw to {{mwalletservice}}">
        <button onclick="location.href='./'">Cancel</button>
    </form>
</div>