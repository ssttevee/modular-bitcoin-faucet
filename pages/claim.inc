<?php
$module = get_module($_GET["game"]);
$faucet = $module->getFaucetInstance($mgr->address);
if($faucet == null) {
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
        <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('bitclix','11724'); ?></div>
        <div id="acwidget"></div>
        <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div><br>
        <input type="submit" value="Collect">
        <button onclick="location.href='./<?= $_GET["game"] ?>.html'">Cancel</button>
    </form>
</div>