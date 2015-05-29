<div class="middle left">
    <?= $top_bar ?>

    <h1>All The Satoshi Beta.</h1>
    <p>Please pick a faucet minigame.</p>

    <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div>

    <ul id="games">
<?php
$m = 0;
        foreach(get_modules() as $module) {
            if($m > 0) echo "        <li class=\"ad banner\"><?php AdManager::insert('bitclix','11724'); ?></li>\n";
            echo "        <li><a href=\"./" . $module . ".html\">" . get_module($module)->name . "</a></li>\n";
            $m++;
        }
        ?>
    </ul>

    <h2>Refer and get 25% of every dispense!</h2>
    Your referral link:<br>
    <input id="ref-url" type="text" size="55" value="<?= $base_url ?>?r={{btcAddress}}" onclick="$(this).select();">
</div>