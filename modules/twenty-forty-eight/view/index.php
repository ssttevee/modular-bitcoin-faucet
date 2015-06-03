<?php
if(!$faucet->isReady()) {
    ob_flush();
    header("Location: ./wait-twenty-forty-eight.html");
}
?>
<div class="middle left">
    <?= $top_bar ?>
    <h1>2048.</h1>
    <p>
        Join the numbers and get satoshi!<br>
        Use your arrow keys to move the tiles<br>
        When two tiles with the same number touch, they merge into one!<br>
    </p><br>
    <div class="ad banner" style="margin: 0 auto;"><?php AdManager::insert('bitclix','11724'); ?></div><br><br>
    <p>Satoshi: {{(score/75)| number:0}}&nbsp;&nbsp;&nbsp;&nbsp;Score: {{score}}</p>
    <div class="twenty-forty-eight" ng-model="score" addr="{{btcAddress}}"></div>
    <button onclick="location.href='./collect-twenty-forty-eight.html'">Collect {{(score/75)| number:0}} Satoshi</button><br>
</div>