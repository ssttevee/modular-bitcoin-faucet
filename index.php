<?php

require_once __DIR__ . "/vendor/autoload.php";

use \AllTheSatoshi\FaucetManager;


if(isset($_GET['r']) && (new FaucetManager($_GET['r']))->claimed > 0) setcookie('ref', $_GET['r'], time() + 3600, '/');
if(isset($_COOKIE['btcAddress'])) $mgr = new FaucetManager($_COOKIE['btcAddress']);


// Top account info bar thing... Kinda clunky and ugly :/
$top_bar = <<<EOF
<div id="top-bar"><span>Payout by <a href="#" ng-click="showCaptcha = true;captchaShowPayout = true;paymentMethod = 'paytoshi';"><b>Paytoshi</b></a> or <a href="javascript:void();" ng-click="showCaptcha = true;captchaShowPayout = true;paymentMethod = 'faucetbox';"><b>FaucetBOX</b></a></span><span>Balance: <b>{{satBalance}}</b> satoshi</span><span id="addr"><b>{{btcAddress}}</b></span></div>
EOF;
if(isset($_POST['event'])) $top_bar .= "\n<div class=\"notice" . ($_POST['event'] == 'error' ? ' red' : '') . "\">". $_POST['message'] ."</div>";


// Page content
if(!isset($_COOKIE['btcAddress'])) $_GET["page"] = "login";
if(!isset($_GET["page"])) $_GET["page"] = "index";

include "template/header.inc";
include "pages/" . $_GET["page"] . ".inc";
include "template/footer.inc";

?>