<?php
ob_start();
require_once __DIR__ . "/vendor/autoload.php";

use \AllTheSatoshi\FaucetManager;


if(isset($_GET['r']) && (new FaucetManager($_GET['r']))->claimed > 0) setcookie('ref', $_GET['r'], time() + 3600, '/');
if(isset($_COOKIE['btcAddress'])) $mgr = FaucetManager::_($_COOKIE['btcAddress']);


// Account info bar
$top_bar = "";
if(isset($_COOKIE['btcAddress'])) $top_bar .= "<div id=\"top-bar\"><span><a href=\"./payout.html\"><b>Withdraw</b></a></span><span>Balance: <b>{{satBalance}}</b> satoshi</span><span id=\"addr\"><b>{{btcAddress}}</b></span></div>";
if(isset($_POST['event'])) $top_bar .= "\n<div class=\"notice" . ($_POST['event'] == 'error' ? ' red' : '') . "\">". $_POST['message'] ."</div>";


// Page content
if(!isset($_COOKIE['btcAddress'])) $_GET["page"] = "login";
if(!isset($_GET["page"])) $_GET["page"] = "index";

include "template/header.inc";
if(is_string(stream_resolve_include_path("pages/" . $_GET["page"] . ".inc"))) {
    include "pages/" . $_GET["page"] . ".inc";
} else {
    header("HTTP/1.0 404 Not Found");
    echo ob_get_clean();
    include "pages/404.inc";
}
include "template/footer.inc";

?>