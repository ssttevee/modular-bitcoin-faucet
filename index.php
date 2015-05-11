<html ng-app="btcFaucetApp">
<head>
    <title>All The Satoshi!</title>
    <link href="style.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js" type="application/javascript"></script>
    <script src="js/controller.js" type="application/javascript"></script>
</head>
<body>
<div class="header">
    <a href="./" class="logo"></a>
    <div class="ad">
        <iframe scrolling="no" frameborder="0" src="//adbit.co/adspace.php?a=1F4DU7F5AB57A" style="overflow:hidden;width:728px;height:90px;"></iframe>
    </div>
</div>
<div class="content">
    <div class="ad left"></div>
    <div class="ad right"></div>
    <div class="middle left" ng-controller="MainFaucetCtrl" ng-init="checkAddress">
        <?php if(!isset($_COOKIE['btcAddress'])) { ?>
            <form method="post" action="login.php">
                <input type="text" name="btcAddress"/>
                <input type="submit" value="Login!"/>
            </form>
        <?php } else { ?>
            <div id="rng-spinner" ng-init="startSpin()">0000</div>
            <div id="rng-value">0 satoshi</div>
            <button id="rng-stop" ng-click="stopSpin()">Stop</button>
        <?php } ?>
    </div>
</div>
</body>
</html>