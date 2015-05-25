<div class="middle left">
    <?= $top_bar ?>

    <h1>All The Satoshi Beta.</h1>
    <p>Please pick a faucet minigame.</p>

    <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div>

    <ul id="games">
        <li><a href="./random-number.html">Random Number Generator</a></li>
        <li class="ad banner"><?php AdManager::insert('bitclix','11724'); ?></li>
        <li><a href="./lucky-joker.html">Lucky Joker</a></li>
    </ul>

    <h2>Refer and get 25% of every dispense!</h2>
    Your referral link:<br>
    <input id="ref-url" type="text" size="55" value="<?= $base_url ?>?r={{btcAddress}}" onclick="$(this).select();">
</div>