<div class="middle left" ng-controller="LoginController">
    <?= $top_bar ?>
    <h1>Welcome to All The Satoshi Beta Faucet.</h1>
    <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div><br>
    <form ng-submit="login()">
        <label for="bitcoin-address">Your Bitcoin Address: </label><br/>
        <input id="bitcoin-address" type="text" name="btcAddress" ng-model="address"/>
        <input type="submit" value="Login!"/>
    </form>
    <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('bitclix','11724'); ?></div>
</div>