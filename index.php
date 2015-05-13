<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require "./lib/AdManager.php";
require "./lib/FaucetManager.php";

if(!isset($_GET["page"])) $_GET["page"] = "index";

if(isset($_GET['r'])) if((new Manager($_GET['r']))->claimed > 0) setcookie('ref', $_GET['r'], time() + 3600, '/');
if(isset($_COOKIE['btcAddress'])) $mgr = new Manager($_COOKIE['btcAddress']);

ob_start();
?>
<div id="top-bar"><span>Payout by <a href="#" ng-click="showCaptcha = true;captchaShowPayout = true;paymentMethod = 'paytoshi';"><b>Paytoshi</b></a> or <a href="javascript:void();" ng-click="showCaptcha = true;captchaShowPayout = true;paymentMethod = 'faucetbox';"><b>FaucetBOX</b></a></span><span>Balance: <b>{{satBalance}}</b> satoshi</span><span id="addr"><b>{{btcAddress}}</b></span></div>
<?php if(isset($_POST['event'])) { ?>
    <?php if($_POST['event'] == 'satoshiclaimed' && isset($_POST['error'])) { ?>
        <div class="notice red">Error: <?= $_POST['error'] ?></div>
    <?php } else if($_POST['event'] == 'satoshiclaimed') { ?>
        <div class="notice"><?= $_POST['amount'] ?> satoshi has been added to your balance!</div>
    <?php } else { ?>
        <div class="notice<?= $_POST['event'] == 'error' ? ' red' : '' ?>"><?= $_POST['message'] ?></div>
    <?php } ?>
<?php } ?>
<?php
$top_bar = ob_get_clean();

include "template/header.inc";
if(!isset($_COOKIE['btcAddress'])) include "pages/login.inc";
else include "pages/" . $_GET["page"] . ".inc";
include "template/footer.inc";

?>