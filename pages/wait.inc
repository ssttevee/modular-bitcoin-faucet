<?php
$module = get_module($_GET["game"]);
$faucet = $module->getFaucetInstance($mgr->address);
if($faucet == null) {
    ob_flush();
    header("Location: ./");
}

?>
<div class="middle left">
    <?= $top_bar ?>
    <h2><?= $module->name ?> on cooldown</h2>
    <div class="ad large-rectangle" style="margin: 0 auto;"><?php AdManager::insert('a-ads','69629'); ?></div>
    <p>please wait...</p>
    <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('bitclix','11724'); ?></div>
    <h1 faucet-cooldown="<?= $faucet->getWaitTime() ?>" on-time-up="window.location.href='./<?= $_GET["game"] ?>.html';"></h1>
</div>