<?php

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$parts = explode('/', $_SERVER['REQUEST_URI']);
unset($parts[count($parts) - 1]);
$base_url = $protocol . $_SERVER['HTTP_HOST'] . join('/',$parts) . '/';
?>
<html ng-app="btcFaucetApp">
<head>
    <title>All The Satoshi! - Earn Free Bitcoins!</title>
    <link href="css/style.css" rel="stylesheet"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular.min.js" type="application/javascript"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.15/angular-cookies.min.js" type="application/javascript"></script>
    <script src="http://api.solvemedia.com/papi/challenge.ajax"></script>
    <script src="js/controller.js" type="application/javascript"></script>
<?php
if(isset($module)) {
    foreach($module->getPageResources() as $resource) {
        if(pathinfo($resource, PATHINFO_EXTENSION) == "js") echo "    <script src=\"assets/" . $module->getUrlSlug() . "/" . $resource . "\" type=\"application/javascript\"></script>\n";
        else echo "    <link href=\"assets/" . $module->getUrlSlug() . "/" . $resource . "\" rel=\"stylesheet\"/>\n";
    }
} else {
    echo "    <link href=\"css/" . $_GET['page'] . ".css\" rel=\"stylesheet\"/>\n";
    echo "    <script src=\"js/" . $_GET['page'] . ".js\" type=\"application/javascript\"></script>\n";
}
?>
    <base href="<?= $base_url ?>">
</head>
<body>
<div class="header">
    <a href="./" class="logo"></a>
    <div class="ad leaderboard"><?php AdManager::insert('bitclix','11719'); ?></div>
</div>
<div class="content" ng-controller="MainContentCtrl">
    <div class="left"><div class="ad medium-rectangle"><?php AdManager::insert('a-ads','69468'); ?></div><br><div class="ad skyscraper"><?php AdManager::insert('adsense','8882945326'); ?></div></div>
    <div class="right"><div class="ad medium-rectangle"><?php AdManager::insert('bitclix','11948'); ?></div><br><div class="ad skyscraper"><?php AdManager::insert('adsense','1220077720'); ?></div></div>