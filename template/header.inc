<?php
$parts = explode('/', $_SERVER['REQUEST_URI']);
unset($parts[count($parts) - 1]);
$base_url = 'http://' . $_SERVER['SERVER_NAME'] . join('/',$parts) . '/';
?>
<html ng-app="btcFaucetApp">
<head>
    <title>All The Satoshi! - Earn Free Bitcoins!</title>
    <link href="css/style.css" rel="stylesheet"/>
    <link href="css/<?= $_GET['page'] ?>.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js" type="application/javascript"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular-cookies.min.js" type="application/javascript"></script>
    <script src="http://api.solvemedia.com/papi/challenge.ajax"></script>
<?= $_GET['page'] == 'stats' ? '    <script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.1.5/highcharts.js"></script>' : ''?>
    <script src="js/controller.js" type="application/javascript"></script>
    <script src="js/<?= $_GET['page'] ?>.js" type="application/javascript"></script>
    <base href="<?= $base_url ?>">
</head>
<body>
<div class="header">
    <a href="./" class="logo"></a>
    <div class="ad leaderboard"><?php AdManager::insert('a-ads','69632'); ?></div>
</div>
<div class="content" ng-controller="MainContentCtrl">
    <div class="ad skyscraper left"><?php AdManager::insert('adsense','8882945326'); ?></div>
    <div class="ad skyscraper right"><?php AdManager::insert('adsense','1220077720'); ?></div>