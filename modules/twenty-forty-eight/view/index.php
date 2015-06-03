<div class="middle left">
    <?= $top_bar ?>
    <h1>2048.</h1>
    <p>
        Join the numbers and get satoshi!<br>
        Use your arrow keys to move the tiles<br>
        When two tiles with the same number touch, they merge into one!<br>
    </p><br><br>
    <p>Satoshi: {{(score/75)| number:0}}&nbsp;&nbsp;&nbsp;&nbsp;Score: {{score}}</p>
    <div class="twenty-forty-eight" ng-model="score" addr="{{btcAddress}}"></div>
</div>